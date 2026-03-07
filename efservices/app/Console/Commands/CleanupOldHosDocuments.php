<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CleanupOldHosDocuments as CleanupJob;

class CleanupOldHosDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hos:cleanup-documents 
                            {--years=7 : Number of years to retain documents}
                            {--dry-run : Run without actually deleting documents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old HOS documents based on retention policy (default: 7 years)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $years = (int) $this->option('years');
        $dryRun = $this->option('dry-run');

        $this->info("Starting HOS documents cleanup...");
        $this->info("Retention period: {$years} years");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No documents will be deleted");
        }

        try {
            if ($dryRun) {
                // In dry-run mode, just show what would be deleted
                $this->performDryRun($years);
            } else {
                // Dispatch the actual cleanup job
                $job = new CleanupJob();
                $job->setRetentionYears($years);
                $job->handle();
                
                $this->info("Cleanup job completed successfully!");
                $this->info("Check logs for detailed information.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Cleanup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Perform a dry run to show what would be deleted.
     */
    protected function performDryRun(int $years): void
    {
        $cutoffDate = now()->subYears($years);
        $collections = ['trip_reports', 'daily_logs', 'monthly_summaries'];

        $this->info("\nDocuments that would be archived and deleted:");
        $this->info("Cutoff date: " . $cutoffDate->toDateTimeString());
        $this->newLine();

        $totalCount = 0;

        foreach ($collections as $collection) {
            $count = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', $collection)
                ->where('created_at', '<', $cutoffDate)
                ->count();

            $this->line("  {$collection}: {$count} documents");
            $totalCount += $count;
        }

        $this->newLine();
        $this->info("Total documents to be processed: {$totalCount}");
        $this->newLine();
        $this->comment("Run without --dry-run to actually perform the cleanup.");
    }
}
