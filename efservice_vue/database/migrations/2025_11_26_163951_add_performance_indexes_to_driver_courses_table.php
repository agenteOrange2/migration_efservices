<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('driver_courses', function (Blueprint $table) {
            // Índice para ordenamiento por defecto (usado en index())
            $table->index('certification_date', 'idx_driver_courses_certification_date');
            
            // Índice para filtrado por estado (usado en filtros)
            $table->index('status', 'idx_driver_courses_status');
            
            // Índice compuesto para consultas comunes que filtran por conductor y ordenan por fecha
            // Esto optimiza la consulta principal del index() que filtra por driver y ordena por fecha
            $table->index(['user_driver_detail_id', 'certification_date'], 'idx_driver_courses_driver_cert_date');
            
            // Índice para búsquedas por organización (usado en filtro de búsqueda)
            $table->index('organization_name', 'idx_driver_courses_organization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_courses', function (Blueprint $table) {
            // Eliminar índices en orden inverso
            $table->dropIndex('idx_driver_courses_organization');
            $table->dropIndex('idx_driver_courses_driver_cert_date');
            $table->dropIndex('idx_driver_courses_status');
            $table->dropIndex('idx_driver_courses_certification_date');
        });
    }
};
