<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\UserDriverDetail;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DriverMedicalController extends Controller
{
    public function index(): Response
    {
        $driver = $this->resolveDriver();
        $driver->load([
            'carrier:id,name',
            'medicalQualification',
        ]);

        return Inertia::render('driver/medical/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'medical' => $driver->medicalQualification
                ? $this->transformMedical($driver->medicalQualification)
                : null,
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

    protected function transformMedical(DriverMedicalQualification $medical): array
    {
        $expirationDate = $medical->medical_card_expiration_date;
        $isExpired = $expirationDate?->isPast() ?? false;
        $daysRemaining = $expirationDate ? now()->startOfDay()->diffInDays($expirationDate->copy()->startOfDay(), false) : null;
        $isExpiringSoon = $expirationDate && ! $isExpired && $daysRemaining !== null && $daysRemaining <= 30;

        $status = 'not_set';

        if ($expirationDate) {
            $status = $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring_soon' : 'valid');
        }

        $documents = collect();
        foreach (['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card', 'social_security_card'] as $collection) {
            $documents = $documents->merge(
                $medical->getMedia($collection)->map(fn (Media $media) => $this->mediaPayload($media, $collection))
            );
        }

        return [
            'id' => $medical->id,
            'social_security_number' => $medical->social_security_number,
            'hire_date' => $medical->hire_date?->format('n/j/Y'),
            'location' => $medical->location,
            'is_suspended' => (bool) $medical->is_suspended,
            'suspension_date' => $medical->suspension_date?->format('n/j/Y'),
            'is_terminated' => (bool) $medical->is_terminated,
            'termination_date' => $medical->termination_date?->format('n/j/Y'),
            'medical_examiner_name' => $medical->medical_examiner_name,
            'medical_examiner_registry_number' => $medical->medical_examiner_registry_number,
            'medical_card_expiration_date' => $expirationDate?->format('n/j/Y'),
            'status' => $status,
            'days_remaining' => $daysRemaining,
            'medical_card_file' => $this->singleMediaPayload($medical, 'medical_card'),
            'social_security_card_file' => $this->singleMediaPayload($medical, 'social_security_card'),
            'documents' => $documents->sortByDesc('created_at_timestamp')->values()->all(),
            'document_counts' => [
                'total' => $documents->count(),
                'medical_card' => $medical->getMedia('medical_card')->count(),
                'social_security_card' => $medical->getMedia('social_security_card')->count(),
                'medical_documents' => $documents->where('collection_name', '!=', 'medical_card')->where('collection_name', '!=', 'social_security_card')->count(),
            ],
        ];
    }

    protected function singleMediaPayload(DriverMedicalQualification $medical, string $collection): ?array
    {
        $media = $medical->getFirstMedia($collection);

        return $media ? $this->mediaPayload($media, $collection) : null;
    }

    protected function mediaPayload(Media $media, string $collection): array
    {
        return [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'size_label' => $media->human_readable_size,
            'collection_name' => $collection,
            'collection_label' => match ($collection) {
                'medical_card' => 'Medical Card',
                'social_security_card' => 'Social Security Card',
                'medical_certificate' => 'Medical Certificate',
                'test_results' => 'Test Results',
                'medical_documents' => 'Medical Documents',
                default => 'Additional Document',
            },
            'file_type' => $this->resolveFileType($media),
            'created_at' => $media->created_at?->format('n/j/Y'),
            'created_at_timestamp' => $media->created_at?->timestamp ?? 0,
        ];
    }

    protected function resolveFileType(Media $media): string
    {
        $mimeType = strtolower((string) $media->mime_type);

        return match (true) {
            str_contains($mimeType, 'pdf') => 'pdf',
            str_contains($mimeType, 'image') => 'image',
            default => pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'file',
        };
    }
}
