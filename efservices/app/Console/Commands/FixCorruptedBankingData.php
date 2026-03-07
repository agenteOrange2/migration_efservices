<?php

namespace App\Console\Commands;

use App\Models\CarrierBankingDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixCorruptedBankingData extends Command
{
    protected $signature = 'banking:fix-corrupted {--dry-run : Show what would be cleared without actually doing it} {--clear : Actually clear the corrupted data}';

    protected $description = 'Identify and optionally clear banking details that cannot be decrypted';

    public function handle()
    {
        $this->info('Checking for corrupted banking details...');

        $allBankingDetails = CarrierBankingDetail::all();
        $corrupted = [];

        foreach ($allBankingDetails as $banking) {
            $hasError = false;
            $corruptedFields = [];

            // Try to access each encrypted field
            $encryptedFields = ['account_number', 'account_holder_name', 'banking_routing_number', 'zip_code', 'security_code'];

            foreach ($encryptedFields as $field) {
                try {
                    $value = $banking->$field;
                } catch (\Exception $e) {
                    $hasError = true;
                    $corruptedFields[] = $field;
                }
            }

            if ($hasError) {
                $corrupted[] = [
                    'id' => $banking->id,
                    'carrier_id' => $banking->carrier_id,
                    'carrier_name' => $banking->carrier ? $banking->carrier->name : 'N/A',
                    'fields' => $corruptedFields,
                    'status' => $banking->status
                ];
            }
        }

        if (empty($corrupted)) {
            $this->info('No corrupted banking details found!');
            return 0;
        }

        $this->warn('Found ' . count($corrupted) . ' corrupted banking detail records:');
        $this->table(
            ['ID', 'Carrier ID', 'Carrier Name', 'Corrupted Fields', 'Status'],
            collect($corrupted)->map(function ($item) {
                return [
                    $item['id'],
                    $item['carrier_id'],
                    $item['carrier_name'],
                    implode(', ', $item['fields']),
                    $item['status']
                ];
            })
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run mode - no changes made');
            return 0;
        }

        if ($this->option('clear')) {
            if (!$this->confirm('Are you sure you want to clear these corrupted records?')) {
                $this->info('Operation cancelled');
                return 0;
            }

            foreach ($corrupted as $item) {
                DB::table('carrier_banking_details')
                    ->where('id', $item['id'])
                    ->update([
                        'account_number' => null,
                        'account_holder_name' => null,
                        'banking_routing_number' => null,
                        'zip_code' => null,
                        'security_code' => null,
                        'status' => 'pending'
                    ]);

                Log::info('Cleared corrupted banking detail', [
                    'banking_detail_id' => $item['id'],
                    'carrier_id' => $item['carrier_id']
                ]);
            }

            $this->info('Cleared ' . count($corrupted) . ' corrupted banking detail records');
            $this->warn('Affected carriers will need to re-enter their banking information');

            return 0;
        }

        $this->info('Use --clear flag to clear the corrupted data, or --dry-run to preview');
        return 0;
    }
}
