<?php

namespace App\Console\Commands;

use App\Models\DriverArchive;
use App\Models\UserDriverDetail;
use App\Services\Driver\DriverArchiveService;
use Illuminate\Console\Command;

class RefreshArchiveSnapshots extends Command
{
    protected $signature = 'archive:refresh-snapshots 
                            {--archive= : Specific archive ID to refresh}
                            {--all : Refresh all archives}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Regenerate snapshots for existing driver archives from original driver data';

    public function handle(DriverArchiveService $archiveService): int
    {
        $archiveId = $this->option('archive');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if (!$archiveId && !$all) {
            $this->error('Please specify --archive=ID or --all');
            return 1;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = DriverArchive::query();
        
        if ($archiveId) {
            $query->where('id', $archiveId);
        }

        $archives = $query->get();

        if ($archives->isEmpty()) {
            $this->error('No archives found');
            return 1;
        }

        $this->info("Processing {$archives->count()} archive(s)...");

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($archives as $archive) {
            $this->line("\nArchive ID: {$archive->id}");
            $this->line("  Original Driver ID: {$archive->original_user_driver_detail_id}");

            // Find the original driver
            $driver = UserDriverDetail::with([
                'user',
                'carrier',
                'licenses.endorsements',
                'medicalQualification',
                'certification',
                'employmentCompanies.company',
                'workHistories',
                'trainingSchools',
                'driverTrainings.training',
                'testings',
                'accidents',
                'trafficConvictions',
                'inspections',
                'vehicleAssignments.vehicle',
            ])->find($archive->original_user_driver_detail_id);

            if (!$driver) {
                $this->warn("  Original driver not found - SKIPPED");
                $skipped++;
                continue;
            }

            $this->line("  Driver Name: {$driver->user->name} {$driver->last_name}");

            // Generate new snapshot
            try {
                $snapshot = $archiveService->generateDriverSnapshot($driver);

                if ($dryRun) {
                    $this->info("  Would update with:");
                    $this->line("    - Licenses: " . count($snapshot['licenses']));
                    $this->line("    - Medical: " . ($snapshot['medical_qualification'] ? 'Yes' : 'No'));
                    $this->line("    - Employment Companies: " . count($snapshot['employment_history']['employment_companies'] ?? []));
                    $this->line("    - Training Schools: " . count($snapshot['training_records']['training_schools'] ?? []));
                    $this->line("    - Testings: " . count($snapshot['testing_records']));
                    $this->line("    - Accidents: " . count($snapshot['accidents']));
                    $this->line("    - Convictions: " . count($snapshot['traffic_convictions']));
                    $this->line("    - Inspections: " . count($snapshot['inspections']));
                    $this->line("    - Vehicle Assignments: " . count($snapshot['vehicle_assignments']));
                } else {
                    // Update the archive with new snapshots using query builder
                    // to bypass the immutability protection in the model
                    \DB::table('driver_archives')
                        ->where('id', $archive->id)
                        ->update([
                            'driver_data_snapshot' => json_encode($snapshot['personal_info']),
                            'licenses_snapshot' => json_encode($snapshot['licenses']),
                            'medical_snapshot' => json_encode($snapshot['medical_qualification']),
                            'certifications_snapshot' => json_encode($snapshot['certifications']),
                            'employment_history_snapshot' => json_encode($snapshot['employment_history']),
                            'training_snapshot' => json_encode($snapshot['training_records']),
                            'testing_snapshot' => json_encode($snapshot['testing_records']),
                            'accidents_snapshot' => json_encode($snapshot['accidents']),
                            'convictions_snapshot' => json_encode($snapshot['traffic_convictions']),
                            'inspections_snapshot' => json_encode($snapshot['inspections']),
                            'hos_snapshot' => json_encode($snapshot['hos_summary']),
                            'vehicle_assignments_snapshot' => json_encode($snapshot['vehicle_assignments']),
                            'updated_at' => now(),
                        ]);

                    $this->info("  UPDATED successfully");
                }

                $updated++;
            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Updated: {$updated}");
        $this->line("  Skipped: {$skipped}");
        $this->line("  Errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
