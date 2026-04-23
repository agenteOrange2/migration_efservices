<?php

namespace App\Console\Commands;

use App\Models\Carrier;
use App\Repositories\CarrierDocumentRepository;
use Illuminate\Console\Command;

class SyncCarrierDocumentStatus extends Command
{
    protected $signature = 'carriers:sync-document-status';

    protected $description = 'Sync the document_status field on all carriers based on their actual document approvals';

    public function handle(CarrierDocumentRepository $repository): int
    {
        $carriers = Carrier::all();
        $total = $carriers->count();

        if ($total === 0) {
            $this->info('No carriers found.');
            return self::SUCCESS;
        }

        $this->info("Syncing document status for {$total} carriers...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($carriers as $carrier) {
            $repository->syncCarrierDocumentStatus($carrier);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done. All carriers have been synced.');

        return self::SUCCESS;
    }
}
