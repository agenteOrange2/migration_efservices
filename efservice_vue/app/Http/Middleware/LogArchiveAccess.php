<?php

namespace App\Http\Middleware;

use App\Models\ArchiveAccessLog;
use App\Models\DriverArchive;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log Archive Access Middleware
 * 
 * Automatically logs all access to driver archives for audit and compliance purposes.
 * Captures view and download actions with full context (user, carrier, IP, etc.).
 */
class LogArchiveAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log successful responses (200-299)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->logAccess($request);
        }

        return $response;
    }

    /**
     * Log the archive access.
     */
    protected function logAccess(Request $request): void
    {
        // Check if route has archive parameter
        if (!$request->route()->hasParameter('archive')) {
            return;
        }

        $archive = $request->route('archive');

        // If archive is an ID, load the model
        if (!$archive instanceof DriverArchive) {
            $archive = DriverArchive::find($archive);
        }

        if (!$archive) {
            return;
        }

        // Determine action type based on route name
        $actionType = $this->determineActionType($request);

        // Get carrier ID from user
        $carrierId = $this->getCarrierId($request);

        if (!$carrierId) {
            return;
        }

        // Prepare metadata
        $metadata = $this->prepareMetadata($request, $actionType);

        // Create log entry
        try {
            ArchiveAccessLog::create([
                'driver_archive_id' => $archive->id,
                'user_id' => auth()->id(),
                'carrier_id' => $carrierId,
                'action_type' => $actionType,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => $metadata,
                'accessed_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to log archive access', [
                'archive_id' => $archive->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine the action type based on the route.
     */
    protected function determineActionType(Request $request): string
    {
        $routeName = $request->route()->getName();

        if (str_contains($routeName, 'download')) {
            return 'download';
        }

        return 'view';
    }

    /**
     * Get carrier ID from the authenticated user.
     */
    protected function getCarrierId(Request $request): ?int
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        // Check if user has carrier details (for carrier employees)
        if ($user->carrierDetails && $user->carrierDetails->carrier_id) {
            return $user->carrierDetails->carrier_id;
        }

        // Check carriers relationship (for carrier owners/managers)
        if ($user->carriers && $user->carriers->isNotEmpty()) {
            return $user->carriers->first()->id;
        }

        // For superadmin, try to get carrier from archive
        if ($user->hasRole('superadmin') && $request->route()->hasParameter('archive')) {
            $archive = $request->route('archive');
            if ($archive instanceof DriverArchive) {
                return $archive->carrier_id;
            }
        }

        return null;
    }

    /**
     * Prepare metadata for the log entry.
     */
    protected function prepareMetadata(Request $request, string $actionType): array
    {
        $metadata = [
            'route' => $request->route()->getName(),
            'method' => $request->method(),
        ];

        // Add download-specific metadata
        if ($actionType === 'download') {
            // File size will be added after download completes
            // For now, just mark it as a download
            $metadata['download_initiated'] = true;
        }

        return $metadata;
    }
}
