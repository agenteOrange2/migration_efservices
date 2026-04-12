<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DriverLicenseController extends Controller
{
    public function index(Request $request): Response
    {
        $driver = $this->resolveDriver();
        $driver->load([
            'carrier:id,name',
            'licenses' => fn ($query) => $query
                ->with('endorsements:id,code,name')
                ->orderByDesc('is_primary')
                ->orderByDesc('expiration_date'),
        ]);

        $licenses = $driver->licenses->map(fn (DriverLicense $license) => $this->transformLicense($license));

        return Inertia::render('driver/licenses/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'stats' => [
                'total' => $licenses->count(),
                'valid' => $licenses->where('status', 'valid')->count(),
                'expiring_soon' => $licenses->where('status', 'expiring_soon')->count(),
                'expired' => $licenses->where('status', 'expired')->count(),
            ],
            'licenses' => $licenses->values(),
        ]);
    }

    public function show(DriverLicense $license): Response
    {
        $driver = $this->resolveDriver();
        $this->authorizeLicense($driver, $license);

        $license->load([
            'endorsements:id,code,name',
            'driverDetail.user:id,name,email',
            'driverDetail.carrier:id,name',
        ]);

        return Inertia::render('driver/licenses/Show', [
            'license' => $this->transformLicenseDetail($license),
        ]);
    }

    protected function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        return $driver;
    }

    protected function authorizeLicense(UserDriverDetail $driver, DriverLicense $license): void
    {
        abort_unless((int) $license->user_driver_detail_id === (int) $driver->id, 403, 'Unauthorized access to this license.');
    }

    protected function transformLicense(DriverLicense $license): array
    {
        [$status, $isExpired, $isExpiringSoon] = $this->resolveStatus($license);

        return [
            'id' => $license->id,
            'license_number' => $license->license_number,
            'license_class' => $license->license_class,
            'state_of_issue' => $license->state_of_issue,
            'expiration_date' => $license->expiration_date?->format('n/j/Y'),
            'is_cdl' => (bool) $license->is_cdl,
            'is_primary' => (bool) $license->is_primary,
            'restrictions' => $license->restrictions,
            'status' => $status,
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'endorsements' => $license->endorsements->map(fn ($endorsement) => [
                'id' => $endorsement->id,
                'code' => $endorsement->code,
                'name' => $endorsement->name,
                'label' => trim(implode(' - ', array_filter([$endorsement->code, $endorsement->name]))) ?: $endorsement->name,
            ])->values(),
            'front_url' => $license->getFirstMediaUrl('license_front') ?: null,
            'back_url' => $license->getFirstMediaUrl('license_back') ?: null,
            'document_count' => $this->licenseDocumentCount($license),
        ];
    }

    protected function transformLicenseDetail(DriverLicense $license): array
    {
        $base = $this->transformLicense($license);

        return array_merge($base, [
            'driver' => $license->driverDetail ? [
                'id' => $license->driverDetail->id,
                'name' => $license->driverDetail->full_name,
                'email' => $license->driverDetail->user?->email,
            ] : null,
            'carrier' => $license->driverDetail?->carrier ? [
                'id' => $license->driverDetail->carrier->id,
                'name' => $license->driverDetail->carrier->name,
            ] : null,
            'created_at' => $license->created_at?->format('n/j/Y g:i A'),
            'updated_at' => $license->updated_at?->format('n/j/Y g:i A'),
            'documents' => $license->getMedia('license_documents')->map(fn (Media $media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'preview_url' => $media->getUrl(),
                'size_label' => $media->human_readable_size,
                'mime_type' => $media->mime_type,
                'file_type' => $this->resolveFileType($media),
                'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
            ])->values(),
        ]);
    }

    protected function resolveStatus(DriverLicense $license): array
    {
        $isExpired = $license->expiration_date?->isPast() ?? false;
        $isExpiringSoon = $license->expiration_date
            && ! $isExpired
            && now()->startOfDay()->diffInDays($license->expiration_date->copy()->startOfDay(), false) <= 30;

        $status = $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring_soon' : 'valid');

        return [$status, $isExpired, $isExpiringSoon];
    }

    protected function licenseDocumentCount(DriverLicense $license): int
    {
        return Media::query()
            ->where('model_type', DriverLicense::class)
            ->where('model_id', $license->id)
            ->whereIn('collection_name', ['license_front', 'license_back', 'license_documents'])
            ->count();
    }

    protected function resolveFileType(Media $media): string
    {
        $mimeType = strtolower((string) $media->mime_type);

        return match (true) {
            str_contains($mimeType, 'pdf') => 'pdf',
            str_contains($mimeType, 'image') => 'image',
            str_contains($mimeType, 'word'),
            str_contains($mimeType, 'officedocument') => 'document',
            default => pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'file',
        };
    }
}
