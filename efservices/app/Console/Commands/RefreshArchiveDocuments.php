<?php

namespace App\Console\Commands;

use App\Models\DriverArchive;
use App\Models\UserDriverDetail;
use App\Services\Driver\DriverArchiveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command to refresh/re-archive documents for existing driver archives.
 * 
 * This is useful when:
 * - Archives were created before the improved archiveMediaFiles() method
 * - Documents need to be re-synchronized from the original driver records
 */
class RefreshArchiveDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archives:refresh-documents 
                            {--archive= : Specific archive ID to refresh}
                            {--all : Refresh all archives}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh/re-archive documents for existing driver archives';

    public function __construct(
        protected DriverArchiveService $archiveService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $archiveId = $this->option('archive');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if (!$archiveId && !$all) {
            $this->error('Please specify --archive=<id> or --all');
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get archives to process
        $query = DriverArchive::query();
        
        if ($archiveId) {
            $query->where('id', $archiveId);
        }

        $archives = $query->get();

        if ($archives->isEmpty()) {
            $this->warn('No archives found to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$archives->count()} archive(s) to process");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        $progressBar = $this->output->createProgressBar($archives->count());
        $progressBar->start();

        foreach ($archives as $archive) {
            $progressBar->advance();

            // Find the original driver
            $driver = UserDriverDetail::find($archive->original_user_driver_detail_id);

            if (!$driver) {
                $this->newLine();
                $this->warn("  ⚠ Archive #{$archive->id}: Original driver not found (ID: {$archive->original_user_driver_detail_id})");
                $skippedCount++;
                continue;
            }

            // Check current document count
            $currentDocCount = $archive->getMedia('archived_documents')->count()
                + $archive->getMedia('archived_licenses')->count()
                + ($archive->getFirstMedia('archived_profile_photo') ? 1 : 0);

            if ($dryRun) {
                // Count documents that would be archived
                $potentialDocs = $this->countPotentialDocuments($driver, $archive->archived_at);
                $this->newLine();
                $this->info("  📦 Archive #{$archive->id} ({$archive->full_name}):");
                $this->line("     Current documents: {$currentDocCount}");
                $this->line("     Potential documents to archive: {$potentialDocs}");
                $successCount++;
                continue;
            }

            try {
                // Clear existing archived documents (to avoid duplicates)
                $archive->clearMediaCollection('archived_documents');
                $archive->clearMediaCollection('archived_licenses');
                $archive->clearMediaCollection('archived_profile_photo');

                // Re-archive documents
                $this->archiveService->archiveMediaFiles($driver, $archive);

                // Get new count
                $newDocCount = $archive->getMedia('archived_documents')->count()
                    + $archive->getMedia('archived_licenses')->count()
                    + ($archive->getFirstMedia('archived_profile_photo') ? 1 : 0);

                $this->newLine();
                $this->info("  ✅ Archive #{$archive->id} ({$archive->full_name}): {$currentDocCount} → {$newDocCount} documents");

                Log::info('Archive documents refreshed', [
                    'archive_id' => $archive->id,
                    'driver_id' => $driver->id,
                    'previous_count' => $currentDocCount,
                    'new_count' => $newDocCount,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  ❌ Archive #{$archive->id}: {$e->getMessage()}");
                
                Log::error('Failed to refresh archive documents', [
                    'archive_id' => $archive->id,
                    'error' => $e->getMessage(),
                ]);

                $failCount++;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Summary:');
        $this->line("   ✅ Successful: {$successCount}");
        $this->line("   ⚠ Skipped: {$skippedCount}");
        $this->line("   ❌ Failed: {$failCount}");

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes.');
        }

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Count potential documents that could be archived from a driver.
     */
    protected function countPotentialDocuments(UserDriverDetail $driver, $cutoffDate): int
    {
        $count = 0;

        // Driver's own media collections
        foreach (['profile_photo_driver', 'license_front', 'license_back', 'trip_reports', 'daily_logs', 'monthly_summaries', 'signatures'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                if ($media->created_at->lte($cutoffDate)) {
                    $count++;
                }
            }
        }

        // Related models
        $relations = [
            'licenses',
            'medicalQualification',
            'testings',
            'accidents',
            'inspections',
            'trainingSchools',
            'trafficConvictions',
        ];

        foreach ($relations as $relation) {
            $related = $driver->$relation;
            
            if (!$related) {
                continue;
            }

            // Handle single model vs collection
            $items = $related instanceof \Illuminate\Database\Eloquent\Collection ? $related : collect([$related]);

            foreach ($items as $item) {
                if (method_exists($item, 'getMedia')) {
                    foreach ($item->getMedia() as $media) {
                        if ($media->created_at->lte($cutoffDate)) {
                            $count++;
                        }
                    }
                }
            }
        }

        return $count;
    }
}
