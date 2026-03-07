<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminStatus
{
    /**
     * Handle an incoming request for admin users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('superadmin')) {
            return redirect()->route('login')
                ->with('error', 'Access denied. Admin privileges required.');
        }

        // Prevent access to driver/carrier areas
        if ($request->is('driver*') || $request->is('carrier/dashboard*')) {
            return redirect()->route('admin.dashboard')
                ->with('warning', 'Please use the admin interface to manage drivers and carriers.');
        }

        // Verify admin dashboard permissions
        if ($request->is('admin*') && !$user->can('view admin dashboard')) {
            return redirect()->route('login')
                ->with('error', 'You do not have permission to access the admin dashboard.');
        }

        return $next($request);
    }
}
