<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PrivateMediaController extends Controller
{
    /**
     * Collections accessible only to admins (any authenticated admin).
     */
    private const ADMIN_ONLY_COLLECTIONS = [
        'carrier_documents',
    ];

    /**
     * Collections accessible to the driver who owns the record,
     * to their carrier admin, or to a superadmin.
     */
    private const DRIVER_COLLECTIONS = [
        'license_front', 'license_back',
        'trip_reports', 'daily_logs', 'monthly_summaries',
        'signatures', 'employment_verification_attempts',
        'w9_documents', 'dot_policy_documents',
        'driving_records', 'criminal_records',
        'medical_records', 'clearing_house',
        'signature',   // VehicleVerificationToken
    ];

    public function serve(Request $request, int $mediaId)
    {
        /** @var Media|null $media */
        $media = Media::find($mediaId);

        if (!$media) {
            abort(404);
        }

        // Only handle media stored on private disk
        if ($media->disk !== 'local') {
            abort(404);
        }

        $this->authorizeMedia($media);

        $path = $media->getPath();

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path, $media->file_name, [
            'Content-Type'        => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    private function authorizeMedia(Media $media): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        // Superadmin can access everything
        if ($user->hasRole('superadmin')) {
            return;
        }

        $collection = $media->collection_name;

        // Admin-only collections require admin role
        if (in_array($collection, self::ADMIN_ONLY_COLLECTIONS, true)) {
            abort_unless($user->hasRole('admin'), 403);
            return;
        }

        // Driver collections: driver owns it, or carrier admin manages the driver
        if (in_array($collection, self::DRIVER_COLLECTIONS, true)) {
            $modelType = $media->model_type;

            // File belongs to a driver detail
            if ($modelType === 'App\\Models\\UserDriverDetail') {
                $driverDetailId = $media->model_id;

                // The driver themselves
                if ($user->driverDetails && (int) $user->driverDetails->id === (int) $driverDetailId) {
                    return;
                }

                // A carrier admin whose carrier owns this driver
                if ($user->carrierDetails) {
                    $carrierId = $user->carrierDetails->carrier_id;
                    $driverBelongsToCarrier = \App\Models\UserDriverDetail::where('id', $driverDetailId)
                        ->where('carrier_id', $carrierId)
                        ->exists();
                    abort_unless($driverBelongsToCarrier, 403);
                    return;
                }
            }

            // Generic: admins can access
            abort_unless($user->hasRole('admin'), 403);
            return;
        }

        abort(403);
    }
}
