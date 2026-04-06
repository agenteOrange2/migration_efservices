<?php

namespace App\Http\Controllers\Admin\Vehicles\Concerns;

use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleMake;
use App\Models\Admin\Vehicle\VehicleType;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait UsesVehicleAdminHelpers
{
    protected function isSuperadmin(): bool
    {
        return (bool) auth()->user()?->hasRole('superadmin');
    }

    protected function currentCarrierId(): ?int
    {
        $carrierId = auth()->user()?->carrier_id;

        return $carrierId ? (int) $carrierId : null;
    }

    protected function resolvedCarrierId(Request $request, string $key = 'carrier_id'): ?int
    {
        if ($this->isSuperadmin()) {
            return $request->filled($key) ? (int) $request->input($key) : null;
        }

        return $this->currentCarrierId();
    }

    protected function applyCarrierScope(Builder $query, ?int $carrierId = null, string $column = 'carrier_id'): void
    {
        if ($this->isSuperadmin()) {
            if ($carrierId) {
                $query->where($column, $carrierId);
            }

            return;
        }

        $query->where($column, $this->currentCarrierId() ?: 0);
    }

    protected function applyVehicleRelationCarrierScope(Builder $query, ?int $carrierId = null, string $relation = 'vehicle'): void
    {
        $query->whereHas($relation, function (Builder $vehicleQuery) use ($carrierId) {
            $this->applyCarrierScope($vehicleQuery, $carrierId);
        });
    }

    protected function carrierOptions(): Collection
    {
        $query = Carrier::query()->orderBy('name');

        if ($this->isSuperadmin()) {
            $query->where('status', Carrier::STATUS_ACTIVE);
        } else {
            $query->where('id', $this->currentCarrierId() ?: 0);
        }

        return $query->get(['id', 'name'])->map(fn (Carrier $carrier) => [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ]);
    }

    protected function driverOptions(?int $carrierId = null): Collection
    {
        $query = UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->whereHas('user', fn (Builder $builder) => $builder->where('status', 1))
            ->orderBy('carrier_id')
            ->orderBy('last_name');

        $this->applyCarrierScope($query, $carrierId);

        return $query->get()->map(fn (UserDriverDetail $driver) => [
            'id' => $driver->id,
            'carrier_id' => $driver->carrier_id,
            'carrier_name' => $driver->carrier?->name,
            'name' => $this->driverFullName($driver),
            'email' => $driver->user?->email,
        ]);
    }

    protected function makeOptions(): Collection
    {
        return VehicleMake::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();
    }

    protected function typeOptions(): Collection
    {
        return VehicleType::query()
            ->orderBy('name')
            ->pluck('name')
            ->values();
    }

    protected function vehicleStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'pending' => 'Pending',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'out_of_service' => 'Out of Service',
        ];
    }

    protected function driverTypeOptions(): array
    {
        return [
            'company' => 'Company Driver',
            'owner_operator' => 'Owner Operator',
            'third_party' => 'Third Party',
        ];
    }

    protected function fuelTypeOptions(): array
    {
        return [
            'Diesel',
            'Gasoline',
            'CNG',
            'LNG',
            'Electric',
            'Hybrid',
        ];
    }

    protected function documentTypeOptions(): array
    {
        return [
            VehicleDocument::DOC_TYPE_REGISTRATION => 'Registration',
            VehicleDocument::DOC_TYPE_INSURANCE => 'Insurance',
            VehicleDocument::DOC_TYPE_ANNUAL_INSPECTION => 'Annual Inspection',
            VehicleDocument::DOC_TYPE_IRP_PERMIT => 'IRP Permit',
            VehicleDocument::DOC_TYPE_IFTA => 'IFTA',
            VehicleDocument::DOC_TYPE_TITLE => 'Title',
            VehicleDocument::DOC_TYPE_LEASE_AGREEMENT => 'Lease Agreement',
            VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD => 'Maintenance Record',
            VehicleDocument::DOC_TYPE_REPAIR_RECORD => 'Repair Record',
            VehicleDocument::DOC_TYPE_EMISSIONS_TEST => 'Emissions Test',
            VehicleDocument::DOC_TYPE_OTHER => 'Other',
        ];
    }

    protected function documentStatusOptions(): array
    {
        return [
            VehicleDocument::STATUS_ACTIVE => 'Active',
            VehicleDocument::STATUS_EXPIRED => 'Expired',
            VehicleDocument::STATUS_PENDING => 'Pending',
            VehicleDocument::STATUS_REJECTED => 'Rejected',
        ];
    }

    protected function driverFullName(?UserDriverDetail $driver): string
    {
        if (! $driver) {
            return 'N/A';
        }

        return trim(implode(' ', array_filter([
            $driver->user?->name,
            $driver->middle_name,
            $driver->last_name,
        ]))) ?: 'N/A';
    }

    protected function parseUsDate(null|string|Carbon $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (! $value) {
            return null;
        }

        $value = trim($value);

        foreach (['Y-m-d', 'n/j/Y', 'm/d/Y', 'n/j/y', 'm/d/y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function formatDateForUi(null|string|Carbon $value, string $format = 'n/j/Y'): ?string
    {
        $date = $value instanceof Carbon ? $value : $this->parseUsDate($value);

        return $date?->format($format);
    }

    protected function titleizeDriverType(?string $driverType): string
    {
        if (! $driverType) {
            return 'Unassigned';
        }

        return match ($driverType) {
            'company' => 'Company Driver',
            'owner_operator' => 'Owner Operator',
            'third_party' => 'Third Party',
            default => str($driverType)->replace('_', ' ')->title()->toString(),
        };
    }
}
