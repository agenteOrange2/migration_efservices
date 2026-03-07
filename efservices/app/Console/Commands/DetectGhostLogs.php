<?php

namespace App\Console\Commands;

use App\Jobs\GhostLogDetectionJob;
use Illuminate\Console\Command;

class DetectGhostLogs extends Command
{
    protected $signature = 'hos:detect-ghost-logs';
    protected $description = 'Detect and process ghost logs (trips that were not closed properly)';

    public function handle(): int
    {
        $this->info('🔍 Detecting ghost logs...');
        
        try {
            // Dispatch the job synchronously
            GhostLogDetectionJob::dispatchSync();
            
            $this->info('✅ Ghost log detection completed successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error detecting ghost logs: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
