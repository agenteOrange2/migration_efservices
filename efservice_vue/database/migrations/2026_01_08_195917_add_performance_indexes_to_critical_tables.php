<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices para tabla vehicles
        if (Schema::hasTable('vehicles')) {
            $this->addIndexIfNotExists('vehicles', 'carrier_id', 'idx_vehicles_carrier_id');
            $this->addIndexIfNotExists('vehicles', 'status', 'idx_vehicles_status');
            $this->addIndexIfNotExists('vehicles', ['carrier_id', 'status'], 'idx_vehicles_carrier_status');
        }

        // Índices para tabla carrier_documents
        if (Schema::hasTable('carrier_documents')) {
            $this->addIndexIfNotExists('carrier_documents', 'carrier_id', 'idx_carrier_docs_carrier_id');
            $this->addIndexIfNotExists('carrier_documents', 'status', 'idx_carrier_docs_status');
            $this->addIndexIfNotExists('carrier_documents', ['carrier_id', 'status'], 'idx_carrier_docs_carrier_status');
        }

        // Índices para tabla driver_documents (si existe)
        if (Schema::hasTable('driver_documents')) {
            $this->addIndexIfNotExists('driver_documents', 'driver_id', 'idx_driver_docs_driver_id');
            $this->addIndexIfNotExists('driver_documents', 'status', 'idx_driver_docs_status');
            $this->addIndexIfNotExists('driver_documents', 'expiration_date', 'idx_driver_docs_expiration');
        }

        // Índices para tabla trips (si existe)
        if (Schema::hasTable('trips')) {
            $this->addIndexIfNotExists('trips', 'user_driver_detail_id', 'idx_trips_driver_id');
            $this->addIndexIfNotExists('trips', 'vehicle_id', 'idx_trips_vehicle_id');
            $this->addIndexIfNotExists('trips', 'status', 'idx_trips_status');
            $this->addIndexIfNotExists('trips', 'scheduled_start_date', 'idx_trips_start_date');
            $this->addIndexIfNotExists('trips', ['user_driver_detail_id', 'status'], 'idx_trips_driver_status');
        }

        // Índices para tabla driver_licenses (si existe)
        if (Schema::hasTable('driver_licenses')) {
            $this->addIndexIfNotExists('driver_licenses', 'user_driver_detail_id', 'idx_driver_licenses_driver_id');
            $this->addIndexIfNotExists('driver_licenses', 'expiration_date', 'idx_driver_licenses_expiration');
            $this->addIndexIfNotExists('driver_licenses', 'status', 'idx_driver_licenses_status');
        }

        // Índices para tabla driver_medical_qualifications (si existe)
        if (Schema::hasTable('driver_medical_qualifications')) {
            $this->addIndexIfNotExists('driver_medical_qualifications', 'user_driver_detail_id', 'idx_medical_driver_id');
            $this->addIndexIfNotExists('driver_medical_qualifications', 'medical_card_expiration_date', 'idx_medical_expiration');
        }

        // Índices adicionales para user_driver_details (complementarios a los existentes)
        if (Schema::hasTable('user_driver_details')) {
            $this->addIndexIfNotExists('user_driver_details', 'created_at', 'idx_user_driver_created_at');
        }

        // Full-text index para búsquedas en users (solo MySQL)
        if (Schema::hasTable('users') && DB::getDriverName() === 'mysql') {
            if (!$this->indexExists('users', 'idx_users_fulltext_search')) {
                try {
                    DB::statement('ALTER TABLE users ADD FULLTEXT idx_users_fulltext_search (name, email)');
                } catch (\Exception $e) {
                    // Error al crear fulltext, continuar
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de vehicles
        if (Schema::hasTable('vehicles')) {
            $this->dropIndexIfExists('vehicles', 'idx_vehicles_carrier_id');
            $this->dropIndexIfExists('vehicles', 'idx_vehicles_status');
            $this->dropIndexIfExists('vehicles', 'idx_vehicles_carrier_status');
        }

        // Eliminar índices de carrier_documents
        if (Schema::hasTable('carrier_documents')) {
            $this->dropIndexIfExists('carrier_documents', 'idx_carrier_docs_carrier_id');
            $this->dropIndexIfExists('carrier_documents', 'idx_carrier_docs_status');
            $this->dropIndexIfExists('carrier_documents', 'idx_carrier_docs_carrier_status');
        }

        // Eliminar índices de driver_documents
        if (Schema::hasTable('driver_documents')) {
            $this->dropIndexIfExists('driver_documents', 'idx_driver_docs_driver_id');
            $this->dropIndexIfExists('driver_documents', 'idx_driver_docs_status');
            $this->dropIndexIfExists('driver_documents', 'idx_driver_docs_expiration');
        }

        // Eliminar índices de trips
        if (Schema::hasTable('trips')) {
            $this->dropIndexIfExists('trips', 'idx_trips_driver_id');
            $this->dropIndexIfExists('trips', 'idx_trips_vehicle_id');
            $this->dropIndexIfExists('trips', 'idx_trips_status');
            $this->dropIndexIfExists('trips', 'idx_trips_start_date');
            $this->dropIndexIfExists('trips', 'idx_trips_driver_status');
        }

        // Eliminar índices de driver_licenses
        if (Schema::hasTable('driver_licenses')) {
            $this->dropIndexIfExists('driver_licenses', 'idx_driver_licenses_driver_id');
            $this->dropIndexIfExists('driver_licenses', 'idx_driver_licenses_expiration');
            $this->dropIndexIfExists('driver_licenses', 'idx_driver_licenses_status');
        }

        // Eliminar índices de driver_medical_qualifications
        if (Schema::hasTable('driver_medical_qualifications')) {
            $this->dropIndexIfExists('driver_medical_qualifications', 'idx_medical_driver_id');
            $this->dropIndexIfExists('driver_medical_qualifications', 'idx_medical_expiration');
        }

        // Eliminar índices adicionales de user_driver_details
        if (Schema::hasTable('user_driver_details')) {
            $this->dropIndexIfExists('user_driver_details', 'idx_user_driver_created_at');
        }

        // Eliminar fulltext index
        if (Schema::hasTable('users') && DB::getDriverName() === 'mysql') {
            if ($this->indexExists('users', 'idx_users_fulltext_search')) {
                try {
                    DB::statement('ALTER TABLE users DROP INDEX idx_users_fulltext_search');
                } catch (\Exception $e) {
                    // Error al eliminar, continuar
                }
            }
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Helper method to add index if it doesn't exist
     */
    private function addIndexIfNotExists(string $tableName, string|array $columns, string $indexName): void
    {
        try {
            if (!$this->indexExists($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                    $table->index($columns, $indexName);
                });
            }
        } catch (\Exception $e) {
            // Index already exists or other error, skip silently
        }
    }

    /**
     * Helper method to drop index if it exists
     */
    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
