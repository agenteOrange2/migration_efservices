<?php

namespace App\Console\Commands;

use App\Models\Carrier;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\UserDriverDetail;
use Illuminate\Console\Command;

/**
 * Print every driver email and every vehicle unit number registered to a
 * carrier, so you can confirm whether a CSV's referenced values actually
 * exist before retrying an HOS import.
 *
 * Examples:
 *   php artisan import:inspect-carrier --carrier=4
 *   php artisan import:inspect-carrier --name="HAILEY TRUCKING"
 */
class InspectCarrierForHosImport extends Command
{
    protected $signature = 'import:inspect-carrier
                            {--carrier= : Carrier id}
                            {--name= : Substring to match against the carrier name}';

    protected $description = "Show drivers and vehicles registered to a carrier (helps debug HOS import 'not found' skips).";

    public function handle(): int
    {
        $carrier = $this->resolveCarrier();

        if (!$carrier) {
            $this->error('Carrier not found. Pass --carrier=<id> or --name="<substring>".');
            return self::FAILURE;
        }

        $this->line('');
        $this->info("Carrier: {$carrier->name}  (id={$carrier->id}, dot={$carrier->dot_number})");
        $this->line('');

        $drivers = UserDriverDetail::with('user:id,email,name')
            ->where('carrier_id', $carrier->id)
            ->get();

        $this->line("<comment>Drivers registered to this carrier ({$drivers->count()})</comment>");
        if ($drivers->isEmpty()) {
            $this->line('   (none)');
        } else {
            $this->table(
                ['driver_id', 'email', 'name', 'status'],
                $drivers->map(fn ($d) => [
                    $d->id,
                    $d->user?->email ?? '—',
                    trim(($d->user?->name ?? '') . ' ' . ($d->last_name ?? '')),
                    $d->status,
                ])->toArray(),
            );
        }

        $vehicles = Vehicle::where('carrier_id', $carrier->id)->get();

        $this->line('');
        $this->line("<comment>Vehicles in this carrier's fleet ({$vehicles->count()})</comment>");
        if ($vehicles->isEmpty()) {
            $this->line('   (none)');
        } else {
            $this->table(
                ['vehicle_id', 'unit_number', 'vin', 'make', 'model', 'status'],
                $vehicles->map(fn ($v) => [
                    $v->id,
                    "'" . ($v->company_unit_number ?? '') . "'", // quote to spot whitespace
                    $v->vin,
                    $v->make,
                    $v->model,
                    $v->status,
                ])->toArray(),
            );
        }

        $this->line('');
        $this->info('Tip: if a CSV cell says "125" and the table shows "\'125 \'" or "\'0125\'", that whitespace/leading-zero is what the import flags as "Vehicle not found".');

        return self::SUCCESS;
    }

    protected function resolveCarrier(): ?Carrier
    {
        if ($id = $this->option('carrier')) {
            return Carrier::find((int) $id);
        }

        if ($name = $this->option('name')) {
            return Carrier::where('name', 'like', '%' . $name . '%')->first();
        }

        return null;
    }
}
