<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithAdminScope
{
    protected function scopeContext(): array
    {
        $user = auth()->user();

        return [
            'is_superadmin' => (bool) ($user?->hasRole('superadmin') ?? false),
            'carrier_id' => $user?->carrierDetails?->carrier_id ? (int) $user->carrierDetails->carrier_id : null,
        ];
    }

    protected function ensureAllowedCarrier(int $carrierId, array $scope): void
    {
        if (! $scope['is_superadmin'] && (int) ($scope['carrier_id'] ?? 0) !== $carrierId) {
            abort(403);
        }
    }

    protected function applyDriverScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        if ($carrierId !== '') {
            $query->where('carrier_id', (int) $carrierId);
            return;
        }

        if (! $scope['is_superadmin']) {
            $query->where('carrier_id', $scope['carrier_id'] ?: 0);
        }
    }

    protected function applyCarrierScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        if ($carrierId !== '') {
            $query->where('id', (int) $carrierId);
            return;
        }

        if (! $scope['is_superadmin']) {
            $query->where('id', $scope['carrier_id'] ?: 0);
        }
    }

    protected function carrierOptions(array $scope): array
    {
        return Carrier::query()
            ->when(! $scope['is_superadmin'], fn (Builder $builder) => $builder->where('id', $scope['carrier_id'] ?: 0))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ])
            ->values()
            ->all();
    }

    protected function driverOptions(array $scope, string $carrierId = '', bool $activeOnly = false): array
    {
        $query = UserDriverDetail::query()->with('user');
        $this->applyDriverScope($query, $scope, $carrierId);

        if ($activeOnly) {
            $query->where('status', UserDriverDetail::STATUS_ACTIVE);
        }

        return $query->orderBy('last_name')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'name' => $driver->full_name ?: ($driver->user?->name ?: 'Unknown Driver'),
            ])
            ->values()
            ->all();
    }

    protected function parseUsDate(?string $value, ?Carbon $default = null): ?Carbon
    {
        if (! $value) {
            return $default?->copy();
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $default?->copy();
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }
}
