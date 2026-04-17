<?php

namespace App\Services;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Search Service
 * 
 * Handles search logic and role-based filtering for the Quick Search feature.
 * Provides navigation search (client-side data) and entity search (database queries).
 */
class SearchService
{
    protected NavigationProvider $navigationProvider;
    
    /**
     * Maximum results per category
     */
    protected const MAX_RESULTS_PER_CATEGORY = 5;
    
    /**
     * Minimum query length for entity search
     */
    protected const MIN_QUERY_LENGTH = 3;

    public function __construct(NavigationProvider $navigationProvider)
    {
        $this->navigationProvider = $navigationProvider;
    }

    protected function resolveRouteUrl(array $candidates, mixed ...$parameters): ?string
    {
        foreach ($candidates as $candidate) {
            if (! Route::has($candidate)) {
                continue;
            }

            try {
                return route($candidate, ...$parameters);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Search across all categories based on user role
     *
     * @param string $query Search query
     * @param User $user Authenticated user
     * @return array Search results grouped by category
     */
    public function search(string $query, User $user): array
    {
        $role = $this->getUserRole($user);
        
        $results = [
            'navigation' => $this->searchNavigation($query, $role),
            'entities' => []
        ];
        
        // Only search entities if query is long enough
        if (strlen(trim($query)) >= self::MIN_QUERY_LENGTH) {
            $results['entities'] = $this->searchEntities($query, $user, $role);
        }
        
        return $results;
    }

    /**
     * Get navigation items for user role
     *
     * @param User $user
     * @return array
     */
    public function getNavigationItems(User $user): array
    {
        $role = $this->getUserRole($user);
        return $this->navigationProvider->getItemsForRole($role);
    }

    /**
     * Get the primary role for a user
     *
     * @param User $user
     * @return string
     */
    protected function getUserRole(User $user): string
    {
        if ($user->hasRole('superadmin')) {
            return 'superadmin';
        }
        
        if ($user->hasRole('user_carrier')) {
            return 'user_carrier';
        }
        
        if ($user->hasRole('user_driver')) {
            return 'user_driver';
        }
        
        return '';
    }

    /**
     * Search navigation items
     *
     * @param string $query
     * @param string $role
     * @return array
     */
    protected function searchNavigation(string $query, string $role): array
    {
        $items = $this->navigationProvider->getItemsForRole($role);
        
        if (empty($query)) {
            return $items;
        }
        
        $query = strtolower(trim($query));
        
        return array_values(array_filter($items, function ($item) use ($query) {
            // Search in title
            if (stripos($item['title'], $query) !== false) {
                return true;
            }
            
            // Search in full path
            if (stripos($item['fullPath'], $query) !== false) {
                return true;
            }
            
            // Search in section
            if (isset($item['section']) && stripos($item['section'], $query) !== false) {
                return true;
            }
            
            // Search in keywords
            if (isset($item['keywords'])) {
                foreach ($item['keywords'] as $keyword) {
                    if (stripos($keyword, $query) !== false) {
                        return true;
                    }
                }
            }
            
            return false;
        }));
    }

    /**
     * Search entities based on role
     *
     * @param string $query
     * @param User $user
     * @param string $role
     * @return array
     */
    protected function searchEntities(string $query, User $user, string $role): array
    {
        $entities = [];
        
        switch ($role) {
            case 'superadmin':                
                $entities['carriers'] = $this->searchCarriers($query);                
                                
                $entities['drivers'] = $this->searchDrivers($query);                
                                
                $entities['vehicles'] = $this->searchVehicles($query);                
                                
                $entities['users'] = $this->searchUsers($query);                
                break;
                
            case 'user_carrier':
                $carrierId = $this->getCarrierIdForUser($user);
                if ($carrierId) {
                    $entities['drivers'] = $this->searchDrivers($query, $carrierId);
                    $entities['vehicles'] = $this->searchVehicles($query, $carrierId);
                }
                break;
                
            case 'user_driver':
                // Drivers don't search entities, only navigation
                break;
        }
        
        // Remove empty categories
        $filtered = array_filter($entities, fn($items) => !empty($items));        
        return $filtered;
    }

    /**
     * Get carrier ID for a carrier user
     *
     * @param User $user
     * @return int|null
     */
    protected function getCarrierIdForUser(User $user): ?int
    {
        if ($user->carrierDetails && $user->carrierDetails->carrier_id) {
            return $user->carrierDetails->carrier_id;
        }
        
        return null;
    }

    /**
     * Search carriers (Admin only)
     *
     * @param string $query
     * @return array
     */
    protected function searchCarriers(string $query): array
    {        
        
        $lowerQuery = strtolower($query);
        
        $carriers = Carrier::where(function ($q) use ($lowerQuery) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . $lowerQuery . '%'])
              ->orWhereRaw('LOWER(COALESCE(dot_number, \'\')) LIKE ?', ['%' . $lowerQuery . '%'])
              ->orWhereRaw('LOWER(COALESCE(mc_number, \'\')) LIKE ?', ['%' . $lowerQuery . '%']);
        })
        ->limit(self::MAX_RESULTS_PER_CATEGORY)
        ->get();
        
        
        return $carriers->map(function ($carrier) {
            $url = $this->resolveRouteUrl([
                'admin.carriers.show',
                'admin.carrier.details',
            ], $carrier);

            if (! $url) {
                return null;
            }
            
            return [
                'id' => 'carrier_' . $carrier->id,
                'type' => 'carrier',
                'title' => $carrier->name,
                'subtitle' => $carrier->dot_number ? "DOT: {$carrier->dot_number}" : ($carrier->mc_number ? "MC: {$carrier->mc_number}" : null),
                'icon' => 'Building2',
                'url' => $url,
                'category' => 'Carriers',
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Search drivers
     * 
     * IMPORTANT: When carrierId is provided, ONLY returns drivers belonging to that carrier.
     * This is a security requirement - carriers must only see their own drivers.
     *
     * @param string $query
     * @param int|null $carrierId Scope to specific carrier (REQUIRED for carrier users)
     * @return array
     */
    protected function searchDrivers(string $query, ?int $carrierId = null): array
    {
        $driversQuery = UserDriverDetail::with(['user', 'licenses']);
        
        // SECURITY: If carrierId is provided, MUST filter by carrier first
        if ($carrierId) {
            $driversQuery->where('carrier_id', $carrierId);
        }
        
        $lowerQuery = strtolower($query);
        $queryWords = array_filter(explode(' ', $lowerQuery)); // Split query into words
        
        // Search in individual fields and user relation
        $driversQuery->where(function ($mainQuery) use ($lowerQuery, $queryWords) {
            // Search full query in user name and email
            $mainQuery->whereHas('user', function ($q) use ($lowerQuery) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $lowerQuery . '%'])
                  ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $lowerQuery . '%']);
            })
            // Search in driver detail fields
            ->orWhereRaw('LOWER(COALESCE(last_name, \'\')) LIKE ?', ['%' . $lowerQuery . '%'])
            ->orWhereRaw('LOWER(COALESCE(middle_name, \'\')) LIKE ?', ['%' . $lowerQuery . '%'])
            // Search in licenses
            ->orWhereHas('licenses', function ($q) use ($lowerQuery) {
                $q->whereRaw('LOWER(license_number) LIKE ?', ['%' . $lowerQuery . '%']);
            });
            
            // Multi-word search: if query has multiple words, search each word across name fields
            if (count($queryWords) > 1) {
                $mainQuery->orWhere(function ($multiWordQuery) use ($queryWords) {
                    foreach ($queryWords as $word) {
                        $multiWordQuery->where(function ($wordQuery) use ($word) {
                            $wordQuery->whereHas('user', function ($q) use ($word) {
                                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $word . '%']);
                            })
                            ->orWhereRaw('LOWER(COALESCE(last_name, \'\')) LIKE ?', ['%' . $word . '%'])
                            ->orWhereRaw('LOWER(COALESCE(middle_name, \'\')) LIKE ?', ['%' . $word . '%']);
                        });
                    }
                });
            }
        });
        
        $drivers = $driversQuery->limit(self::MAX_RESULTS_PER_CATEGORY)->get();
        
        return $drivers->map(function ($driver) use ($carrierId) {
            $url = $carrierId
                ? $this->resolveRouteUrl(['carrier.drivers.show'], $driver->id)
                : $this->resolveRouteUrl(['admin.drivers.show'], $driver->id);

            if (! $url) {
                return null;
            }
            
            return [
                'id' => 'driver_' . $driver->id,
                'type' => 'driver',
                'title' => $driver->full_name ?? ($driver->user->name ?? 'Unknown'),
                'subtitle' => $driver->user->email ?? null,
                'icon' => 'User',
                'url' => $url,
                'category' => 'Drivers',
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Search vehicles
     * 
     * IMPORTANT: When carrierId is provided, ONLY returns vehicles belonging to that carrier.
     * This is a security requirement - carriers must only see their own vehicles.
     *
     * @param string $query
     * @param int|null $carrierId Scope to specific carrier (REQUIRED for carrier users)
     * @return array
     */
    protected function searchVehicles(string $query, ?int $carrierId = null): array
    {
        $vehiclesQuery = Vehicle::query();
        
        // SECURITY: If carrierId is provided, MUST filter by carrier first
        if ($carrierId) {
            $vehiclesQuery->where('carrier_id', $carrierId);
        }
        
        $lowerQuery = strtolower($query);
        
        // Then apply search filters within the carrier scope (case-insensitive)
        $vehiclesQuery->where(function ($q) use ($lowerQuery) {
            $q->whereRaw('LOWER(vin) LIKE ?', ['%' . $lowerQuery . '%'])
              ->orWhereRaw('LOWER(company_unit_number) LIKE ?', ['%' . $lowerQuery . '%'])
              ->orWhereRaw('LOWER(registration_number) LIKE ?', ['%' . $lowerQuery . '%'])
              ->orWhereRaw('LOWER(make) LIKE ?', ['%' . $lowerQuery . '%'])
              ->orWhereRaw('LOWER(model) LIKE ?', ['%' . $lowerQuery . '%']);
        });
        
        $vehicles = $vehiclesQuery->limit(self::MAX_RESULTS_PER_CATEGORY)->get();
        
        return $vehicles->map(function ($vehicle) use ($carrierId) {
            $url = $carrierId
                ? $this->resolveRouteUrl(['carrier.vehicles.show'], $vehicle->id)
                : $this->resolveRouteUrl(['admin.vehicles.show'], $vehicle->id);

            if (! $url) {
                return null;
            }
            
            $subtitle = $vehicle->company_unit_number 
                ? "Unit: {$vehicle->company_unit_number}" 
                : ($vehicle->vin ? "VIN: " . substr($vehicle->vin, -6) : null);
            
            return [
                'id' => 'vehicle_' . $vehicle->id,
                'type' => 'vehicle',
                'title' => trim("{$vehicle->year} {$vehicle->make} {$vehicle->model}") ?: 'Unknown Vehicle',
                'subtitle' => $subtitle,
                'icon' => 'Truck',
                'url' => $url,
                'category' => 'Vehicles',
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Search users (Admin only)
     *
     * @param string $query
     * @return array
     */
    protected function searchUsers(string $query): array
    {
        $lowerQuery = strtolower($query);
        
        $users = User::where(function ($q) use ($lowerQuery) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . $lowerQuery . '%'])
              ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $lowerQuery . '%']);
        })
        ->limit(self::MAX_RESULTS_PER_CATEGORY)
        ->get();
        
        return $users->map(function ($user) {
            $url = $this->resolveRouteUrl(['admin.users.show'], $user->id);

            if (! $url) {
                return null;
            }
            
            return [
                'id' => 'user_' . $user->id,
                'type' => 'user',
                'title' => $user->name,
                'subtitle' => $user->email,
                'icon' => 'UserCircle',
                'url' => $url,
                'category' => 'Users',
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Get quick actions based on user role
     *
     * @param User $user
     * @return array
     */
    public function getQuickActions(User $user): array
    {
        $role = $this->getUserRole($user);
        $actions = [];
        
        switch ($role) {
            case 'superadmin':
                if (\Route::has('admin.carriers.create')) {
                    $actions[] = ['title' => 'Add Carrier', 'icon' => 'Plus', 'url' => route('admin.carriers.create')];
                }
                if (\Route::has('admin.drivers.wizard.create')) {
                    $actions[] = ['title' => 'Add Driver', 'icon' => 'UserPlus', 'url' => route('admin.drivers.wizard.create')];
                }
                if (\Route::has('admin.reports.index')) {
                    $actions[] = ['title' => 'View Reports', 'icon' => 'BarChart3', 'url' => route('admin.reports.index')];
                }
                break;
                
            case 'user_carrier':
                if (\Route::has('carrier.drivers.create')) {
                    $actions[] = ['title' => 'Add Driver', 'icon' => 'UserPlus', 'url' => route('carrier.drivers.create')];
                }
                if (\Route::has('carrier.vehicles.create')) {
                    $actions[] = ['title' => 'Add Vehicle', 'icon' => 'Truck', 'url' => route('carrier.vehicles.create')];
                }
                if (\Route::has('carrier.hos.dashboard')) {
                    $actions[] = ['title' => 'View HOS', 'icon' => 'Clock', 'url' => route('carrier.hos.dashboard')];
                }
                if (\Route::has('carrier.reports.index')) {
                    $actions[] = ['title' => 'View Reports', 'icon' => 'BarChart3', 'url' => route('carrier.reports.index')];
                }
                break;
                
            case 'user_driver':
                if (\Route::has('driver.profile')) {
                    $actions[] = ['title' => 'My Profile', 'icon' => 'User', 'url' => route('driver.profile')];
                }
                if (\Route::has('driver.hos.dashboard')) {
                    $actions[] = ['title' => 'View HOS', 'icon' => 'Clock', 'url' => route('driver.hos.dashboard')];
                }
                if (\Route::has('driver.documents.index')) {
                    $actions[] = ['title' => 'My Documents', 'icon' => 'FileText', 'url' => route('driver.documents.index')];
                }
                if (\Route::has('driver.trips.index')) {
                    $actions[] = ['title' => 'My Trips', 'icon' => 'MapPin', 'url' => route('driver.trips.index')];
                }
                break;
        }
        
        return $actions;
    }
}
