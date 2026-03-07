<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cacheable Lists Trait
 * 
 * Proporciona funcionalidad para cachear listas de selección comunes
 * usadas en el registro de drivers (estados, países, tipos de vehículos, etc.)
 */
trait CacheableListsTrait
{
    /**
     * TTL del cache en minutos (60 minutos = 1 hora)
     */
    protected int $cacheMinutes = 60;

    /**
     * Prefijo para las claves de cache
     */
    protected string $listsCachePrefix = 'driver_registration';

    /**
     * Obtener lista de estados de USA cacheada
     *
     * @return array
     */
    public function getCachedStates(): array
    {
        return Cache::remember(
            $this->listsCachePrefix . ':states',
            $this->cacheMinutes * 60,
            function () {
                return $this->loadStates();
            }
        );
    }

    /**
     * Obtener lista de países cacheada
     *
     * @return array
     */
    public function getCachedCountries(): array
    {
        return Cache::remember(
            $this->listsCachePrefix . ':countries',
            $this->cacheMinutes * 60,
            function () {
                return $this->loadCountries();
            }
        );
    }

    /**
     * Obtener lista de marcas de vehículos cacheada
     *
     * @return array
     */
    public function getCachedVehicleMakes(): array
    {
        return Cache::remember(
            $this->listsCachePrefix . ':vehicle_makes',
            $this->cacheMinutes * 60,
            function () {
                return $this->loadVehicleMakes();
            }
        );
    }

    /**
     * Obtener lista de tipos de vehículos cacheada
     *
     * @return array
     */
    public function getCachedVehicleTypes(): array
    {
        return Cache::remember(
            $this->listsCachePrefix . ':vehicle_types',
            $this->cacheMinutes * 60,
            function () {
                return $this->loadVehicleTypes();
            }
        );
    }

    /**
     * Obtener lista de tipos de licencia cacheada
     *
     * @return array
     */
    public function getCachedLicenseTypes(): array
    {
        return Cache::remember(
            $this->listsCachePrefix . ':license_types',
            $this->cacheMinutes * 60,
            function () {
                return $this->loadLicenseTypes();
            }
        );
    }

    /**
     * Obtener lista de endorsements de licencia cacheada
     *
     * @return array
     */
    public function getCachedLicenseEndorsements(): array
    {
        return Cache::remember(
            $this->listsCachePrefix . ':license_endorsements',
            $this->cacheMinutes * 60,
            function () {
                return $this->loadLicenseEndorsements();
            }
        );
    }

    /**
     * Obtener lista de tipos de experiencia cacheada
     *
     * @return array
     */
    public function getCachedExperienceTypes(): array
    {
        return Cache::remember(
            $this->listsCachePrefix . ':experience_types',
            $this->cacheMinutes * 60,
            function () {
                return $this->loadExperienceTypes();
            }
        );
    }

    /**
     * Limpiar todo el cache de listas
     *
     * @return void
     */
    public function clearListsCache(): void
    {
        $keys = [
            ':states',
            ':countries',
            ':vehicle_makes',
            ':vehicle_types',
            ':license_types',
            ':license_endorsements',
            ':experience_types',
        ];

        foreach ($keys as $key) {
            Cache::forget($this->listsCachePrefix . $key);
        }
    }

    /**
     * Limpiar cache de una lista específica
     *
     * @param string $listName
     * @return void
     */
    public function clearSpecificListCache(string $listName): void
    {
        Cache::forget($this->listsCachePrefix . ':' . $listName);
    }

    /**
     * Cargar estados de USA
     *
     * @return array
     */
    protected function loadStates(): array
    {
        return [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'DC' => 'District of Columbia',
        ];
    }


    /**
     * Cargar países
     *
     * @return array
     */
    protected function loadCountries(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'MX' => 'Mexico',
        ];
    }

    /**
     * Cargar marcas de vehículos
     *
     * @return array
     */
    protected function loadVehicleMakes(): array
    {
        // Intentar cargar de la base de datos si existe la tabla
        try {
            if (DB::getSchemaBuilder()->hasTable('vehicle_makes')) {
                return DB::table('vehicle_makes')
                    ->where('active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // Fallback a lista estática
        }

        return [
            'Freightliner',
            'Kenworth',
            'Peterbilt',
            'Volvo',
            'International',
            'Mack',
            'Western Star',
            'Navistar',
            'Hino',
            'Isuzu',
        ];
    }

    /**
     * Cargar tipos de vehículos
     *
     * @return array
     */
    protected function loadVehicleTypes(): array
    {
        return [
            'tractor' => 'Tractor',
            'straight_truck' => 'Straight Truck',
            'trailer' => 'Trailer',
            'bus' => 'Bus',
            'van' => 'Van',
            'tanker' => 'Tanker',
            'flatbed' => 'Flatbed',
            'refrigerated' => 'Refrigerated',
            'dry_van' => 'Dry Van',
            'car_hauler' => 'Car Hauler',
        ];
    }

    /**
     * Cargar tipos de licencia
     *
     * @return array
     */
    protected function loadLicenseTypes(): array
    {
        return [
            'A' => 'Class A CDL',
            'B' => 'Class B CDL',
            'C' => 'Class C CDL',
            'D' => 'Class D (Regular)',
        ];
    }

    /**
     * Cargar endorsements de licencia
     *
     * @return array
     */
    protected function loadLicenseEndorsements(): array
    {
        // Intentar cargar de la base de datos si existe la tabla
        try {
            if (DB::getSchemaBuilder()->hasTable('license_endorsements')) {
                return DB::table('license_endorsements')
                    ->orderBy('code')
                    ->pluck('name', 'code')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // Fallback a lista estática
        }

        return [
            'H' => 'Hazardous Materials',
            'N' => 'Tank Vehicles',
            'P' => 'Passenger',
            'S' => 'School Bus',
            'T' => 'Double/Triple Trailers',
            'X' => 'Combination of Tank and Hazmat',
        ];
    }

    /**
     * Cargar tipos de experiencia
     *
     * @return array
     */
    protected function loadExperienceTypes(): array
    {
        return [
            'straight_truck' => 'Straight Truck',
            'tractor_semi_trailer' => 'Tractor Semi-Trailer',
            'tractor_two_trailers' => 'Tractor Two Trailers',
            'tractor_three_trailers' => 'Tractor Three Trailers',
            'motorcoach' => 'Motorcoach (more than 15 passengers)',
            'school_bus' => 'School Bus (more than 15 passengers)',
        ];
    }

    /**
     * Obtener el TTL del cache en segundos
     *
     * @return int
     */
    public function getCacheTTL(): int
    {
        return $this->cacheMinutes * 60;
    }

    /**
     * Establecer el TTL del cache en minutos
     *
     * @param int $minutes
     * @return void
     */
    public function setCacheTTL(int $minutes): void
    {
        $this->cacheMinutes = max(1, $minutes);
    }

    /**
     * Verificar si una lista está en cache
     *
     * @param string $listName
     * @return bool
     */
    public function isListCached(string $listName): bool
    {
        return Cache::has($this->listsCachePrefix . ':' . $listName);
    }

    /**
     * Obtener todas las listas cacheadas de una vez
     * Útil para precargar datos en el frontend
     *
     * @return array
     */
    public function getAllCachedLists(): array
    {
        return [
            'states' => $this->getCachedStates(),
            'countries' => $this->getCachedCountries(),
            'vehicle_makes' => $this->getCachedVehicleMakes(),
            'vehicle_types' => $this->getCachedVehicleTypes(),
            'license_types' => $this->getCachedLicenseTypes(),
            'license_endorsements' => $this->getCachedLicenseEndorsements(),
            'experience_types' => $this->getCachedExperienceTypes(),
        ];
    }
}
