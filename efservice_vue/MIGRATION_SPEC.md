# EFServices Migration Spec
## Blade + Livewire → Vue 3 + Inertia.js

> **Origen:** `efservices/` (Laravel 11, Blade, Livewire, Jetstream)
> **Destino:** `efservice_vue/` (Laravel 12, Vue 3, Inertia.js, Fortify, Tailwind v4)

---

## 1. Arquitectura General

### 1.1 Stack Destino
| Capa | Tecnología |
|------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3.5 (Composition API + `<script setup>`) |
| Routing SPA | Inertia.js v2 (`@inertiajs/vue3`) |
| State | Pinia |
| Estilos | Tailwind CSS v4 + `tw-animate-css` |
| Componentes UI | Reka UI + Headless UI + Lucide Icons |
| Charts | Chart.js |
| Dates | Day.js |
| Forms | Inertia `useForm()` |
| Tipos | TypeScript estricto |
| Build | Vite 7 + SSR |
| Auth | Laravel Fortify (sin Jetstream) |
| Permisos | Spatie Laravel Permission |
| Exports | Maatwebsite Excel + DomPDF |
| Media | Spatie Media Library |
| Rutas JS | Ziggy + Laravel Wayfinder |

### 1.2 Estructura de Carpetas Frontend

```
resources/js/
├── app.ts                          # Entry point (Inertia + Pinia + Ziggy)
├── ssr.ts                          # Server-side rendering
├── assets/                         # Imágenes estáticas
├── components/
│   ├── Base/                       # Componentes base (Alert, Table, Form, etc.)
│   ├── ui/                         # shadcn-vue components
│   ├── shared/                     # Componentes compartidos entre módulos
│   │   ├── DataTable.vue           # Tabla genérica con paginación, filtros, export
│   │   ├── FileUploader.vue        # Upload de archivos con preview
│   │   ├── StatusBadge.vue         # Badges de estado reutilizables
│   │   ├── ConfirmDialog.vue       # Diálogo de confirmación
│   │   ├── SearchBar.vue           # Barra de búsqueda global
│   │   ├── FilterPopover.vue       # Filtros avanzados
│   │   ├── ExportMenu.vue          # Menú de exportación (PDF, Excel)
│   │   ├── NotificationCenter.vue  # Panel de notificaciones
│   │   └── StepWizard.vue          # Wizard multi-paso reutilizable
│   ├── App*.vue                    # Componentes de app shell
│   └── Nav*.vue                    # Navegación
├── composables/
│   ├── useAppearance.ts            # Tema claro/oscuro
│   ├── usePermissions.ts           # Verificación de permisos Spatie
│   ├── useFilters.ts               # Filtros de tabla reutilizables
│   ├── useExport.ts                # Lógica de exportación
│   ├── useNotifications.ts         # Sistema de notificaciones
│   ├── useWizard.ts                # Lógica de wizard multi-paso
│   ├── useDebounce.ts              # Debounce para búsquedas
│   └── useConfirmation.ts          # Confirmación antes de acciones
├── layouts/
│   ├── AppLayout.vue               # Layout principal autenticado
│   ├── AuthLayout.vue              # Layout de autenticación
│   ├── RazeLayout.vue              # Layout tema Raze
│   ├── AdminLayout.vue             # (CREAR) Layout admin con sidebar admin
│   ├── CarrierLayout.vue           # (CREAR) Layout carrier con sidebar carrier
│   └── DriverLayout.vue            # (CREAR) Layout driver con sidebar driver
├── pages/
│   ├── auth/                       # ✅ Ya migrado
│   ├── settings/                   # ✅ Ya migrado
│   ├── admin/                      # (CREAR) Páginas del superadmin
│   │   ├── Dashboard.vue
│   │   ├── carriers/
│   │   ├── drivers/
│   │   ├── vehicles/
│   │   ├── reports/
│   │   ├── users/
│   │   ├── messages/
│   │   ├── settings/
│   │   ├── hos/
│   │   └── ...
│   ├── carrier/                    # (CREAR) Páginas del carrier
│   │   ├── Dashboard.vue
│   │   ├── wizard/
│   │   ├── drivers/
│   │   ├── vehicles/
│   │   ├── reports/
│   │   ├── messages/
│   │   ├── hos/
│   │   └── ...
│   └── driver/                     # (CREAR) Páginas del driver
│       ├── Dashboard.vue
│       ├── registration/
│       ├── profile/
│       ├── vehicles/
│       ├── hos/
│       ├── trips/
│       ├── messages/
│       └── ...
├── routes/                         # Wayfinder auto-generated
├── Themes/                         # Configuración de temas
└── types/
    ├── index.ts                    # Re-exports
    ├── auth.ts                     # Tipos de autenticación
    ├── models.ts                   # (CREAR) Tipos TypeScript de todos los Models
    ├── carrier.ts                  # (CREAR) Tipos específicos de carrier
    ├── driver.ts                   # (CREAR) Tipos específicos de driver
    ├── vehicle.ts                  # (CREAR) Tipos específicos de vehículo
    ├── hos.ts                      # (CREAR) Tipos específicos de HOS
    └── enums.ts                    # (CREAR) Enums compartidos
```

---

## 2. Backend: Configuración Pendiente

### 2.1 Providers por Registrar

Archivo `bootstrap/providers.php` actualmente solo tiene:
```php
return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
];
```

**Agregar:**
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\ServiceLayerServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\PermissionServiceProvider::class,
    App\Providers\AppUrlServiceProvider::class,
];
```

**NO registrar:**
- `JetstreamServiceProvider` → No se usa Jetstream
- `ViewServiceProvider` → View Composers son de Blade, no aplican

### 2.2 Middleware por Registrar

Archivo `bootstrap/app.php` actualmente tiene:
```php
$middleware->web(append: [
    HandleAppearance::class,
    HandleInertiaRequests::class,
    AddLinkHeadersForPreloadedAssets::class,
]);
```

**Agregar alias de middleware:**
```php
$middleware->alias([
    'check.role.access'       => \App\Http\Middleware\CheckRoleAccess::class,
    'check.admin.status'      => \App\Http\Middleware\CheckAdminStatus::class,
    'check.carrier.status'    => \App\Http\Middleware\CheckCarrierStatus::class,
    'check.driver.status'     => \App\Http\Middleware\CheckDriverStatus::class,
    'check.permission'        => \App\Http\Middleware\CheckPermission::class,
    'check.user.status'       => \App\Http\Middleware\CheckUserStatus::class,
    'ensure.carrier.registered' => \App\Http\Middleware\EnsureCarrierRegistered::class,
    'api.rate.limit'          => \App\Http\Middleware\ApiRateLimit::class,
    'json.response'           => \App\Http\Middleware\JsonResponseMiddleware::class,
    'log.archive.access'      => \App\Http\Middleware\LogArchiveAccess::class,
    'prevent.mass.assignment'  => \App\Http\Middleware\PreventMassAssignment::class,
    'security.headers'        => \App\Http\Middleware\SecurityHeaders::class,
    'validate.upload.session'  => \App\Http\Middleware\ValidateUploadSession::class,
]);
```

### 2.3 HandleInertiaRequests - Datos Compartidos

Actualizar `share()` para incluir datos que necesitan todos los módulos:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'auth' => [
            'user' => $request->user() ? $request->user()->load('roles', 'permissions') : null,
        ],
        'ziggy' => fn () => [
            ...(new \Tighten\Ziggy\Ziggy)->toArray(),
            'location' => $request->url(),
        ],
        'sidebarOpen' => ! $request->hasCookie('sidebar_state')
            || $request->cookie('sidebar_state') === 'true',
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error'   => fn () => $request->session()->get('error'),
            'warning' => fn () => $request->session()->get('warning'),
            'info'    => fn () => $request->session()->get('info'),
        ],
        'notifications' => fn () => $request->user()
            ? $request->user()->unreadNotifications()->latest()->take(10)->get()
            : [],
        'unreadNotificationsCount' => fn () => $request->user()
            ? $request->user()->unreadNotifications()->count()
            : 0,
    ];
}
```

### 2.4 Rutas por Crear

Crear los siguientes archivos de rutas (adaptados a Inertia):

| Archivo | Prefijo | Middleware Base | Estado |
|---------|---------|-----------------|--------|
| `routes/web.php` | `/` | `web` | ✅ Existe (actualizar) |
| `routes/settings.php` | `/settings` | `web, auth` | ✅ Existe |
| `routes/admin.php` | `/admin` | `web, auth, check.role.access:superadmin, check.admin.status` | ❌ Crear |
| `routes/carrier.php` | `/carrier` | `web, check.role.access:user_carrier` | ❌ Crear |
| `routes/driver.php` | `/driver` | `web, check.role.access:user_driver` | ❌ Crear |
| `routes/api.php` | `/api` | `api` | ❌ Crear |
| `routes/hos.php` | incluido en web | `auth, role:*` | ❌ Crear |

**Registrar en `bootstrap/app.php`:**
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web', 'auth', 'check.role.access:superadmin', 'check.admin.status')
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));

        Route::middleware(['web', 'check.role.access:user_carrier'])
            ->prefix('carrier')
            ->name('carrier.')
            ->group(base_path('routes/carrier.php'));

        Route::middleware(['web', 'check.role.access:user_driver'])
            ->prefix('driver')
            ->name('driver.')
            ->group(base_path('routes/driver.php'));
    },
)
```

---

## 3. Patrón de Migración Controller

### 3.1 Antes (Blade + Livewire)
```php
// Controller devuelve vista Blade
public function index()
{
    $carriers = Carrier::paginate(15);
    return view('admin.carriers.index', compact('carriers'));
}
```

### 3.2 Después (Inertia + Vue)
```php
// Controller devuelve página Inertia
use Inertia\Inertia;
use Inertia\Response;

public function index(): Response
{
    $carriers = Carrier::paginate(15);
    return Inertia::render('admin/carriers/Index', [
        'carriers' => $carriers,
        'filters'  => request()->only(['search', 'status']),
    ]);
}
```

### 3.3 Reglas de Conversión

| Blade/Livewire | Inertia/Vue |
|-----------------|-------------|
| `return view('admin.carriers.index', [...])` | `return Inertia::render('admin/carriers/Index', [...])` |
| `return redirect()->route(...)` | `return redirect()->route(...)` (igual) |
| `return redirect()->back()` | `return back()` (igual) |
| `with('success', '...')` | `with('success', '...')` (via flash en share) |
| Componente Livewire con estado | Componente Vue con `ref()` / `reactive()` |
| `wire:model` | `v-model` + `useForm()` |
| `wire:click` | `@click` + método del componente |
| `$this->emit('event')` | `emit()` o Pinia store |
| `$this->dispatch('event')` | Event bus o Pinia |
| Livewire polling | `setInterval` + `router.reload()` |

---

## 4. Patrón de Migración Vista

### 4.1 Estructura de Página Vue

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DataTable from '@/components/shared/DataTable.vue'
import type { Carrier, PaginatedResponse } from '@/types'

interface Props {
    carriers: PaginatedResponse<Carrier>
    filters: {
        search?: string
        status?: string
    }
}

const props = defineProps<Props>()
</script>

<template>
    <Head title="Carriers" />
    <AdminLayout>
        <div class="p-6">
            <h1 class="text-2xl font-bold">Carriers</h1>
            <DataTable
                :data="carriers"
                :filters="filters"
                :columns="columns"
            />
        </div>
    </AdminLayout>
</template>
```

### 4.2 Mapeo de Componentes Livewire → Vue

| Livewire (efservices) | Vue (efservice_vue) |
|------------------------|---------------------|
| `GenericTable.php` | `components/shared/DataTable.vue` |
| `FilterPopover.php` | `components/shared/FilterPopover.vue` |
| `SearchBar.php` | `components/shared/SearchBar.vue` |
| `MenuExport.php` | `components/shared/ExportMenu.vue` |
| `PaginationLinks.php` | `components/shared/Pagination.vue` (o Base/Pagination) |
| `FileUploader.php` | `components/shared/FileUploader.vue` |
| `NotificationCenter.php` | `components/shared/NotificationCenter.vue` |
| `Driver\RegistrationManager.php` | `components/shared/StepWizard.vue` + páginas |
| `Admin\Driver\Driver*Step.php` | `pages/admin/drivers/steps/*.vue` |
| `Carrier\Driver\DriverRegistrationManager.php` | `pages/carrier/drivers/registration/*.vue` |

---

## 5. Roles y Layouts

### 5.1 Mapeo de Roles a Layouts

| Rol | Layout Blade (origen) | Layout Vue (destino) | Sidebar Menu |
|-----|----------------------|----------------------|--------------|
| `superadmin` | `layouts/admin.blade.php` | `AdminLayout.vue` | `SideMenu.php` |
| `user_carrier` | `layouts/carrier.blade.php` | `CarrierLayout.vue` | `CarrierSideMenu.php` |
| `user_driver` | `layouts/driver.blade.php` | `DriverLayout.vue` | `DriverSideMenu.php` |
| Guest | `layouts/guest.blade.php` | `AuthLayout.vue` | N/A |

### 5.2 Crear Layouts Específicos

Cada layout debe:
1. Heredar la estructura de `AppLayout.vue` (sidebar + header + content)
2. Usar el sidebar menu correspondiente (`app/Main/SideMenu.php`, etc.)
3. Pasar el menú como prop desde `HandleInertiaRequests` o computarlo en el layout

---

## 6. Módulos de Migración (Orden de Ejecución)

### Fase 1: Infraestructura Base
| # | Tarea | Prioridad |
|---|-------|-----------|
| 1.1 | Configurar providers, middleware y rutas en `bootstrap/` | CRÍTICA |
| 1.2 | Ejecutar migraciones (`php artisan migrate`) | CRÍTICA |
| 1.3 | Crear tipos TypeScript para todos los Models | ALTA |
| 1.4 | Crear composables compartidos (`usePermissions`, `useFilters`, etc.) | ALTA |
| 1.5 | Crear componentes compartidos (`DataTable`, `FileUploader`, etc.) | ALTA |
| 1.6 | Crear layouts (`AdminLayout`, `CarrierLayout`, `DriverLayout`) | ALTA |

### Fase 2: Autenticación y Onboarding
| # | Módulo | Controllers | Páginas Vue |
|---|--------|-------------|-------------|
| 2.1 | Carrier Registration Wizard | `CarrierWizardController`, `CarrierOnboardingController`, `CarrierDocumentController`, `CarrierStatusController` | `carrier/wizard/Step1..4.vue` |
| 2.2 | Driver Registration | `DriverRegistrationController` | `driver/registration/*.vue` |

### Fase 3: Admin (Superadmin)
| # | Módulo | Controllers | Páginas Vue |
|---|--------|-------------|-------------|
| 3.1 | Admin Dashboard | `DashboardController` | `admin/Dashboard.vue` |
| 3.2 | Admin Carriers | `CarrierController`, `CarrierDocumentController` | `admin/carriers/Index.vue`, `Show.vue`, `Edit.vue` |
| 3.3 | Admin Users | `UserController` | `admin/users/Index.vue`, `Create.vue`, `Edit.vue` |
| 3.4 | Admin Drivers List | `DriversController`, `DriverListController` | `admin/drivers/Index.vue` |
| 3.5 | Admin Driver Detail | `AdminDriverController`, `DriverDocumentsController`, `DriverLicensesController`, `MedicalRecordsController`, `TrainingSchoolsController`, `TrafficConvictionsController`, `AccidentsController`, `InspectionsController`, `DriverTestingController`, `SocialSecurityCardController`, `CoursesController`, `DocumentsController`, `DriverTypeController`, `EmploymentVerificationAdminController` | `admin/drivers/Show.vue`, `admin/drivers/tabs/*.vue` |
| 3.6 | Admin Driver Registration | `UserDriverController` (wizard admin) | `admin/drivers/create/*.vue` |
| 3.7 | Admin Vehicles | `VehicleController`, `VehicleDashboardController`, `VehicleDocumentController`, `VehicleMaintenanceController`, `VehicleDriverAssignmentController`, `MaintenanceController`, `MaintenanceReportController`, `EmergencyRepairController` | `admin/vehicles/*.vue` |
| 3.8 | Admin HOS | `HosController`, `HosDocumentController`, `AdminDriverHosController` | `admin/hos/*.vue` |
| 3.9 | Admin Reports | `ReportsController` | `admin/reports/*.vue` |
| 3.10 | Admin Training | `TrainingDashboardController`, `TrainingAssignmentsController` | `admin/training/*.vue` |
| 3.11 | Admin Messages | `MessagesController` | `admin/messages/*.vue` |
| 3.12 | Admin Settings | `SettingsController`, `MembershipController`, `DocumentTypeController`, `MasterCompanyController`, `NotificationRecipientsController` | `admin/settings/*.vue` |
| 3.13 | Admin Misc | `ContactSubmissionController`, `PlanRequestController`, `BulkImportController`, `SafetyDataSystemController`, `W9Controller`, `ApprovedDriversController`, `UserCarrierDocumentController` | `admin/misc/*.vue` |

### Fase 4: Carrier
| # | Módulo | Controllers | Páginas Vue |
|---|--------|-------------|-------------|
| 4.1 | Carrier Dashboard | `CarrierDashboardController` | `carrier/Dashboard.vue` |
| 4.2 | Carrier Profile | `CarrierProfileController` | `carrier/Profile.vue` |
| 4.3 | Carrier Drivers | `CarrierDriverController`, `CarrierDriverManagementController`, `CarrierDriverDocumentsController`, `CarrierDriverLicensesController`, `CarrierDriverAccidentsController`, `CarrierDriverTestingsController`, `CarrierDriverInspectionsController`, `CarrierMedicalRecordsController`, `CarrierTrafficController`, `CarrierTrainingSchoolsController`, `InactiveDriversController` | `carrier/drivers/*.vue` |
| 4.4 | Carrier Vehicles | `CarrierVehicleController`, `CarrierVehicleDocumentController`, `CarrierVehicleDocumentsOverviewController`, `CarrierVehicleMaintenanceController`, `CarrierDriverVehicleManagementController`, `CarrierEmergencyRepairController`, `VehicleMakeController`, `VehicleTypeController` | `carrier/vehicles/*.vue` |
| 4.5 | Carrier HOS | `HosController`, `HosDocumentController`, `HosConfigurationController`, `CarrierDriverHosController`, `ViolationController` | `carrier/hos/*.vue` |
| 4.6 | Carrier Trips | `TripController` | `carrier/trips/*.vue` |
| 4.7 | Carrier Reports | `CarrierReportsController` | `carrier/reports/*.vue` |
| 4.8 | Carrier Training | `CarrierTrainingsController`, `CarrierTrainingAssignmentsController` | `carrier/training/*.vue` |
| 4.9 | Carrier Messages | `MessagesController` | `carrier/messages/*.vue` |

### Fase 5: Driver
| # | Módulo | Controllers | Páginas Vue |
|---|--------|-------------|-------------|
| 5.1 | Driver Dashboard | `DriverDashboardController` | `driver/Dashboard.vue` |
| 5.2 | Driver Profile | `DriverProfileController` | `driver/Profile.vue` |
| 5.3 | Driver Vehicles | `DriverVehicleController`, `DriverMaintenanceController`, `DriverEmergencyRepairController` | `driver/vehicles/*.vue` |
| 5.4 | Driver Documents | `DriverDocumentController`, `DriverLicenseController`, `DriverMedicalController` | `driver/documents/*.vue` |
| 5.5 | Driver HOS | `HosController`, `HosDocumentController`, `DriverHosCycleController` | `driver/hos/*.vue` |
| 5.6 | Driver Trips | `TripController` | `driver/trips/*.vue` |
| 5.7 | Driver Testing | `DriverTestingController`, `DriverInspectionController` | `driver/testing/*.vue` |
| 5.8 | Driver Training | `DriverTrainingController` | `driver/training/*.vue` |
| 5.9 | Driver Messages | `MessagesController` | `driver/messages/*.vue` |

### Fase 6: API
| # | Módulo | Controllers |
|---|--------|-------------|
| 6.1 | API Controllers | `CarrierApiController`, `DriverDocumentApiController`, `DriverTabsApiController`, `AdminDriverApiController`, `SearchController`, `UploadController`, `UserDriverApiController`, `ZipCodeController` |

---

## 7. Tipos TypeScript

### 7.1 Tipos Base (crear en `resources/js/types/models.ts`)

```typescript
// Paginación de Laravel
export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

// Flash messages (desde HandleInertiaRequests)
export interface FlashMessages {
    success?: string
    error?: string
    warning?: string
    info?: string
}

// Shared page props
export interface SharedPageProps {
    name: string
    auth: {
        user: User | null
    }
    flash: FlashMessages
    notifications: Notification[]
    unreadNotificationsCount: number
    sidebarOpen: boolean
    ziggy: {
        url: string
        port: number | null
        defaults: Record<string, unknown>
        routes: Record<string, unknown>
        location: string
    }
}
```

### 7.2 Tipos de Modelos (generar para cada Model)

Cada Model de Laravel se mapea a una interface TypeScript. Ejemplo:

```typescript
export interface Carrier {
    id: number
    name: string
    slug: string
    dot_number: string | null
    mc_number: string | null
    status: CarrierStatus
    documents_completed: boolean
    created_at: string
    updated_at: string
    // Relaciones
    users?: User[]
    documents?: CarrierDocument[]
    vehicles?: Vehicle[]
}

export type CarrierStatus = 'pending' | 'active' | 'inactive' | 'suspended'
```

---

## 8. Componentes Compartidos Críticos

### 8.1 DataTable (reemplaza GenericTable de Livewire)

Funcionalidades requeridas:
- Paginación server-side via Inertia
- Búsqueda con debounce
- Ordenamiento por columnas
- Filtros avanzados (popover)
- Selección múltiple de filas
- Exportación (PDF, Excel) via API
- Columnas configurables
- Responsive
- Loading states

### 8.2 StepWizard (reemplaza RegistrationManager de Livewire)

Funcionalidades requeridas:
- Pasos dinámicos con validación por paso
- Navegación entre pasos (anterior/siguiente)
- Indicador de progreso
- Auto-guardado (usando `useForm` de Inertia)
- Validación server-side por paso
- Soporte para subir archivos en cualquier paso

### 8.3 FileUploader (reemplaza FileUploader de Livewire)

Funcionalidades requeridas:
- Drag & drop
- Preview de archivos (imágenes, PDFs)
- Validación de tipos y tamaños
- Barra de progreso
- Upload múltiple
- Integración con Spatie Media Library

---

## 9. Convenciones de Código

### 9.1 Nomenclatura de Archivos

| Tipo | Convención | Ejemplo |
|------|-----------|---------|
| Página Vue | PascalCase | `pages/admin/carriers/Index.vue` |
| Componente | PascalCase | `components/shared/DataTable.vue` |
| Composable | camelCase con `use` | `composables/usePermissions.ts` |
| Tipo TS | PascalCase | `types/models.ts` → `export interface Carrier` |
| Layout | PascalCase + Layout | `layouts/AdminLayout.vue` |
| Store Pinia | camelCase con `use` + Store | `stores/useCarrierStore.ts` |

### 9.2 Controllers Inertia

```php
// Siempre tipar el retorno
public function index(): \Inertia\Response { ... }
public function store(StoreCarrierRequest $request): \Illuminate\Http\RedirectResponse { ... }

// Usar Form Requests existentes (ya copiados)
// Usar Services existentes (ya copiados)
// Usar Repositories existentes (ya copiados)
```

### 9.3 Componentes Vue

```vue
<!-- Siempre usar Composition API + script setup + TypeScript -->
<script setup lang="ts">
// 1. Imports
// 2. Props & Emits (con tipos)
// 3. Composables
// 4. Reactive state
// 5. Computed
// 6. Methods
// 7. Lifecycle hooks
</script>

<template>
    <!-- Template con Tailwind v4 -->
</template>
```

### 9.4 Formularios Inertia

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    email: '',
    status: 'active',
})

function submit() {
    form.post(route('admin.carriers.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>
```

---

## 10. Consideraciones Especiales

### 10.1 Middleware de Redirect

Los middleware `CheckCarrierStatus`, `CheckDriverStatus`, etc. actualmente hacen `redirect()` a rutas Blade. Deben actualizarse para redirigir a rutas Inertia equivalentes.

### 10.2 Componentes Livewire con Estado Complejo

Los wizards de registro de drivers (`DriverRegistrationManager`, etc.) tienen estado complejo en Livewire. En Vue, este estado se manejará con:
- `useForm()` de Inertia para datos del formulario
- `ref()`/`reactive()` para estado local del wizard
- Endpoints de API para auto-guardado parcial
- Pinia store si el estado debe compartirse entre componentes

### 10.3 Notificaciones en Tiempo Real

El `NotificationCenter` de Livewire usa polling. En Vue se puede:
- Usar `setInterval` + `router.reload({ only: ['notifications'] })`
- O implementar Laravel Echo + Pusher/Reverb para websockets

### 10.4 Exports (PDF/Excel)

Los exports no cambian en backend. En frontend:
- El botón de export hace un `window.open(route('admin.reports.export'))` o
- Usa `axios` para descargar el archivo como blob

### 10.5 Spatie Media Library

Ya funciona en backend. En frontend:
- Los archivos se suben via `useForm()` con `forceFormData: true`
- O via endpoint API dedicado con `axios`

### 10.6 Spatie Permissions en Frontend

Compartir permisos del usuario en `HandleInertiaRequests`:
```php
'auth' => [
    'user' => $request->user()?->load('roles', 'permissions'),
],
```

Composable `usePermissions.ts`:
```typescript
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const page = usePage()
    const user = computed(() => page.props.auth.user)

    function can(permission: string): boolean {
        return user.value?.permissions?.some(p => p.name === permission) ?? false
    }

    function hasRole(role: string): boolean {
        return user.value?.roles?.some(r => r.name === role) ?? false
    }

    return { can, hasRole }
}
```

---

## 11. Cómo Solicitar Migraciones

Al pedir la migración de un módulo, usar este formato:

> **"Migra el módulo [NOMBRE] de la Fase [N.M]"**

Ejemplo: *"Migra el módulo Admin Carriers de la Fase 3.2"*

Esto creará:
1. El Controller adaptado a Inertia
2. Las rutas correspondientes
3. Las páginas Vue
4. Los tipos TypeScript necesarios

El asistente leerá automáticamente:
- El Controller original en `efservices/`
- Las vistas Blade originales
- Los Services y Requests ya copiados
- Los Models ya copiados

---

## 12. Resumen de Archivos por Crear

### Backend
| Tipo | Cantidad Estimada |
|------|-------------------|
| Controllers (nuevos, adaptados a Inertia) | ~99 |
| Archivos de rutas | 5 (`admin.php`, `carrier.php`, `driver.php`, `api.php`, `hos.php`) |
| Actualizar `bootstrap/app.php` | 1 |
| Actualizar `bootstrap/providers.php` | 1 |
| Actualizar `HandleInertiaRequests.php` | 1 |

### Frontend
| Tipo | Cantidad Estimada |
|------|-------------------|
| Layouts (nuevos) | 3 (`AdminLayout`, `CarrierLayout`, `DriverLayout`) |
| Páginas Vue | ~120-150 |
| Componentes compartidos | ~10-15 |
| Composables | ~8-10 |
| Tipos TypeScript | ~6-8 archivos |
| Stores Pinia | ~3-5 |

---

## 13. Dependencias NPM Adicionales Sugeridas

```bash
# Ya instaladas (verificar versiones)
# @inertiajs/vue3, vue, pinia, axios, chart.js, dayjs, lucide-vue-next

# Opcionales útiles
npm install @vueuse/motion          # Animaciones
npm install vue-toastification      # Toasts mejorados (o usar toastify-js ya instalado)
npm install @tanstack/vue-table     # Tabla avanzada (alternativa a DataTable custom)
```

---

*Spec generado el 2026-03-05. Actualizar conforme avance la migración.*
