# Refactor: Unificación del esquema de usuarios

> **Estado:** Pendiente — implementar al terminar el proyecto  
> **Prioridad:** Alta (deuda técnica estructural)  
> **Riesgo:** Alto — requiere migración de datos y actualización de modelos, controllers y frontend

---

## Problema actual

El esquema actual reparte datos de **identidad de persona** entre tres tablas:

| Campo          | `users` | `user_carrier_details` | `user_driver_details` |
|----------------|:-------:|:----------------------:|:---------------------:|
| `name`         | ✅      | —                      | — (solo first name)   |
| `middle_name`  | ❌      | —                      | ✅                    |
| `last_name`    | ❌      | —                      | ✅                    |
| `phone`        | ❌      | ✅                      | ✅                    |
| `date_of_birth`| ❌      | —                      | ✅                    |
| `email`        | ✅      | —                      | —                     |

### Consecuencias
- Para obtener el nombre completo de un driver hay que hacer join con `user_driver_details`.
- `phone` existe duplicado en dos tablas con lógica separada.
- Un usuario que cambia de rol (carrier → driver) no tiene portabilidad de datos.
- Las queries de búsqueda por nombre/teléfono son inconsistentes entre contextos.

---

## Estructura objetivo

### `users` (tabla base — agregar campos)

```sql
ALTER TABLE users
    RENAME COLUMN name TO first_name,          -- aclarar semántica
    ADD COLUMN middle_name VARCHAR(255) NULL AFTER first_name,
    ADD COLUMN last_name   VARCHAR(255) NULL AFTER middle_name,
    ADD COLUMN phone       VARCHAR(30)  NULL AFTER last_name,
    ADD COLUMN date_of_birth DATE       NULL AFTER phone;
```

**Estado final de `users`:**
```
id | first_name | middle_name | last_name | email | phone | date_of_birth
   | email_verified_at | password | remember_token | profile_photo_path
   | status | access_type | current_team_id | timestamps
```

---

### `user_carrier_details` (limpiar)

**Quitar:** `phone`  
**Dejar:** `id, user_id, carrier_id, job_position, status, confirmation_token, timestamps`

```sql
ALTER TABLE user_carrier_details DROP COLUMN phone;
```

---

### `user_driver_details` (limpiar)

**Quitar:** `middle_name`, `last_name`, `phone`, `date_of_birth`  
**Dejar todo lo demás** — son campos específicos del workflow de driver:

```
id | user_id | carrier_id | status | terms_accepted | confirmation_token
   | application_completed | current_step | completion_percentage
   | use_custom_dates | custom_created_at | has_completed_employment_history
   | custom_registration_date | custom_completion_date
   | hire_date | termination_date
   | created_by_admin | updated_by_admin | timestamps
```

---

## Plan de migración paso a paso

### Fase 1 — Migración de datos (sin romper nada todavía)

Crear una sola migration que:

1. Agrega los nuevos campos a `users` (nullable para no romper existentes).
2. Copia los datos existentes de `user_driver_details` → `users`.
3. Copia `phone` de `user_carrier_details` → `users` (solo si `users.phone` está vacío).

```php
// database/migrations/XXXX_refactor_users_consolidate_identity_fields.php

public function up(): void
{
    // 1. Agregar columnas a users
    Schema::table('users', function (Blueprint $table) {
        $table->renameColumn('name', 'first_name');
        $table->string('middle_name')->nullable()->after('first_name');
        $table->string('last_name')->nullable()->after('middle_name');
        $table->string('phone', 30)->nullable()->after('last_name');
        $table->date('date_of_birth')->nullable()->after('phone');
    });

    // 2. Migrar datos de drivers
    DB::statement("
        UPDATE users u
        INNER JOIN user_driver_details udd ON udd.user_id = u.id
        SET
            u.middle_name    = udd.middle_name,
            u.last_name      = udd.last_name,
            u.phone          = udd.phone,
            u.date_of_birth  = udd.date_of_birth
    ");

    // 3. Migrar phone de carriers (solo donde users.phone sea null)
    DB::statement("
        UPDATE users u
        INNER JOIN user_carrier_details ucd ON ucd.user_id = u.id
        SET u.phone = ucd.phone
        WHERE u.phone IS NULL
    ");
}
```

### Fase 2 — Limpiar tablas _details

```php
Schema::table('user_driver_details', function (Blueprint $table) {
    $table->dropColumn(['middle_name', 'last_name', 'phone', 'date_of_birth']);
});

Schema::table('user_carrier_details', function (Blueprint $table) {
    $table->dropColumn('phone');
});
```

> ⚠️ **Hacer esto en una migration separada**, después de verificar que los datos se migraron correctamente y que el código ya fue actualizado.

---

## Archivos que necesitan actualizarse

### Modelos

#### `app/Models/User.php`
- Agregar a `$fillable`: `first_name`, `middle_name`, `last_name`, `phone`, `date_of_birth`
- Agregar accessor `full_name`:
```php
public function getFullNameAttribute(): string
{
    return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
}
```
- Quitar cualquier lógica que lea `name` como nombre completo.

#### `app/Models/UserDriverDetail.php`
- Quitar de `$fillable`: `middle_name`, `last_name`, `phone`, `date_of_birth`
- Actualizar cualquier accessor `full_name` para delegar a `$this->user->full_name`

#### `app/Models/UserCarrierDetail.php`  
- Quitar de `$fillable`: `phone`

---

### Controllers que leen/escriben estos campos

| Controller | Cambio |
|---|---|
| `DriverAdminWizardController` | Leer/escribir `phone`, `date_of_birth`, `last_name`, `middle_name` desde `$driver->user` en vez de `$driver` |
| `CarrierDriverController` | Igual que arriba |
| `DriverListController` (`transformDriver`) | Leer `full_name` de `user->full_name`, `phone` de `user->phone` |
| `CarrierWizardController` | Leer/escribir `phone` desde `user` en vez de `user_carrier_details` |
| `HandleInertiaRequests` (`getUserData`) | Incluir nuevos campos en el array del usuario autenticado |

---

### Wizard (frontend + backend)

El wizard de drivers actualmente guarda en **Step 1**:
- `last_name`, `middle_name` → mover a `users`
- `phone`, `date_of_birth` → mover a `users`

El `updateStep` del wizard necesita apuntar al modelo correcto:
```php
// Antes:
$driver->update(['last_name' => $data['last_name'], 'phone' => $data['phone']]);

// Después:
$driver->user->update(['last_name' => $data['last_name'], 'phone' => $data['phone']]);
$driver->update([/* solo campos propios del driver detail */]);
```

---

### Imports (`UserCarriersImport`, etc.)

Revisar columnas mapeadas en los imports masivos — actualizar para escribir en `users` los campos de identidad.

---

### Búsquedas / Queries

Actualmente hay `whereHas('user', fn($q) => $q->where('name', 'like', ...))` mezclado con `orWhere('last_name', ...)` en `user_driver_details`. Después del refactor, todo queda en `users`:

```php
// Después del refactor, búsqueda unificada:
$query->whereHas('user', function ($q) use ($search) {
    $q->where('first_name', 'like', "%{$search}%")
      ->orWhere('last_name',  'like', "%{$search}%")
      ->orWhere('email',      'like', "%{$search}%")
      ->orWhere('phone',      'like', "%{$search}%");
});
```

---

## Riesgos y consideraciones

| Riesgo | Mitigación |
|---|---|
| Datos de producción pueden perderse si la migración de copia falla | Hacer backup completo antes. Correr Fase 1 primero, validar, luego Fase 2. |
| `name` → `first_name` rompe todo lo que usa `$user->name` | Agregar accessor temporal: `public function getNameAttribute() { return $this->first_name; }` durante la transición |
| Imports masivos CSV que mapean a columnas de _details | Revisar y actualizar `UserCarriersImport` y similares |
| Spatie Media Library usa `user_driver_details` como modelo | No se afecta — Media Library trabaja con el modelo, no con columnas específicas |
| Auth/session — `Auth::user()->name` usado en RazeLayout | Cubierto por el accessor temporal de `name` |

---

## Orden de ejecución recomendado

```
1. Backup base de datos producción
2. Rama git: refactor/user-schema-consolidation
3. Crear migration Fase 1 (agregar columnas + copiar datos)
4. Actualizar modelos (User, UserDriverDetail, UserCarrierDetail)
5. Actualizar controllers (wizard, list, carrier)
6. Actualizar frontend (wizard steps, show pages, búsquedas)
7. Actualizar imports CSV
8. QA completo en staging
9. Crear migration Fase 2 (DROP columnas de _details)
10. Deploy a producción
```

---

## Resultado esperado

- Una sola fuente de verdad para datos de identidad de persona.
- Búsquedas unificadas y más eficientes.
- Wizard de drivers más limpio (Step 1 actualiza `users` directamente).
- Portabilidad de datos si un usuario cambia de rol.
- Menos joins en queries frecuentes.
