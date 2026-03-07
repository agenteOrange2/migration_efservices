# 🚛 Sistema de Gestión de Transportistas (TMS)

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Sistema SaaS completo para la gestión integral de empresas de transporte, conductores, vehículos y cumplimiento regulatorio (DOT, FMCSR).

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Uso](#-uso)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Arquitectura](#-arquitectura)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

## ✨ Características

### Gestión de Transportistas (Carriers)
- ✅ Registro multi-paso con wizard guiado
- ✅ Gestión de documentación corporativa (COI, W9, etc.)
- ✅ Planes de membresía con límites configurables
- ✅ Información bancaria y facturación
- ✅ Token de referencia para invitar conductores

### Gestión de Conductores (Drivers)
- ✅ Proceso de aplicación completo (FMCSR compliant)
- ✅ Gestión de licencias CDL/Non-CDL
- ✅ Historial laboral (últimos 3 años)
- ✅ Certificaciones médicas (DOT Physical)
- ✅ Registro de accidentes e infracciones
- ✅ Pruebas de drogas y alcohol con sistema mejorado
- ✅ Sistema de entrenamientos

#### Sistema de Pruebas de Drogas y Alcohol (Driver Testing)
- ✅ Formularios de creación/edición con carga dinámica de conductores
- ✅ Vista detallada moderna con componentes card-based
- ✅ Manejo robusto de errores con timeouts y reintentos
- ✅ Notificaciones toast con iconos y auto-dismiss
- ✅ Formateo consistente de datos (fechas, nombres, badges)
- ✅ Caché inteligente para listas de carriers y conductores
- ✅ Previsualización de PDFs con opciones de descarga
- ✅ Historial de pruebas por conductor
- ✅ Múltiples tipos de pruebas (DOT, Non-DOT, Random, etc.)
- ✅ Integración con Spatie Media Library para documentos

### Gestión de Vehículos
- ✅ Registro y documentación de vehículos
- ✅ Asignación de conductores (Company/Owner Operator/Third Party)
- ✅ Mantenimiento preventivo y correctivo
- ✅ Calendario de mantenimientos
- ✅ Reparaciones de emergencia
- ✅ Historial completo de asignaciones

### Cumplimiento Regulatorio
- ✅ Verificación de empleo (Employment Verification)
- ✅ Verificación de vehículos de terceros
- ✅ Gestión de documentos FMCSR
- ✅ Inspecciones DOT
- ✅ Reportes de cumplimiento

### Sistema de Entrenamientos
- ✅ Creación y asignación de capacitaciones
- ✅ Seguimiento de progreso
- ✅ Documentación de completitud
- ✅ Certificados digitales

### Reportes y Dashboard
- ✅ Dashboard con métricas en tiempo real
- ✅ Exportación de reportes (PDF, Excel)
- ✅ Notificaciones de vencimientos
- ✅ Sistema de mensajería interna

## 🔧 Requisitos

### Requisitos del Sistema
- **PHP**: 8.2 o superior
- **Composer**: 2.x
- **Node.js**: 18.x o superior
- **NPM**: 9.x o superior
- **Base de Datos**: MySQL 8.0+ o PostgreSQL 13+
- **Redis**: 6.x o superior (para caché y colas)
- **Extensiones PHP requeridas**:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD o Imagick (para procesamiento de imágenes)

### Requisitos Opcionales
- **Supervisor**: Para gestión de colas en producción
- **Nginx/Apache**: Servidor web
- **SSL Certificate**: Para HTTPS en producción

## 📦 Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/carrier-management-system.git
cd carrier-management-system
```

### 2. Instalar Dependencias PHP

```bash
composer install
```

### 3. Instalar Dependencias JavaScript

```bash
npm install
```

### 4. Configurar Variables de Entorno

```bash
cp .env.example .env
```

Edita el archivo `.env` con tus configuraciones:

```env
APP_NAME="Carrier Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carrier_management
DB_USERNAME=root
DB_PASSWORD=

# Redis (Requerido)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Notificaciones Admin
ADMIN_NOTIFICATION_EMAIL=admin@example.com
```

### 5. Generar Key de Aplicación

```bash
php artisan key:generate
```

### 6. Ejecutar Migraciones

```bash
php artisan migrate
```

### 7. Ejecutar Seeders (Opcional)

```bash
php artisan db:seed
```

### 8. Crear Link de Storage

```bash
php artisan storage:link
```

### 9. Compilar Assets

```bash
# Desarrollo
npm run dev

# Producción
npm run build
```

### 10. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

La aplicación estará disponible en `http://localhost:8000`

## ⚙️ Configuración

### Configuración de Roles y Permisos

El sistema utiliza Spatie Laravel Permission. Los roles predeterminados son:

- **superadmin**: Acceso completo al sistema
- **admin**: Gestión de carriers, drivers y vehículos
- **user_carrier**: Administrador de empresa de transporte
- **user_driver**: Conductor con acceso limitado

Para crear roles y permisos:

```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
```

### Configuración de Colas

Para procesar trabajos en segundo plano:

```bash
# Desarrollo
php artisan queue:work

# Producción (usar Supervisor)
php artisan queue:work --tries=3 --timeout=90
```

### Configuración de Tareas Programadas

Agregar al crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🎯 Uso

### Acceso al Sistema

#### SuperAdmin
- URL: `http://localhost:8000/admin`
- Crear usuario admin manualmente o via seeder

#### Carrier (Transportista)
1. Registrarse en: `http://localhost:8000/carrier/register`
2. Completar wizard de 4 pasos
3. Esperar aprobación de administrador
4. Acceder al dashboard

#### Driver (Conductor)
1. Recibir invitación del carrier (token único)
2. Completar proceso de aplicación
3. Esperar aprobación del carrier
4. Acceder al dashboard

### Flujos Principales

#### Registro de Carrier
```
1. Paso 1: Información básica → Crear cuenta
2. Paso 2: Información de empresa → DOT, MC, EIN
3. Paso 3: Selección de membresía → Límites
4. Paso 4: Información bancaria → Pago
5. Validación por admin → Activación
```

#### Aplicación de Driver
```
1. Registro con token del carrier
2. Información personal y contacto
3. Licencias y experiencia
4. Historial laboral (3 años)
5. Documentos y certificaciones
6. Aprobación del carrier
```

#### Asignación de Vehículo
```
1. Crear/Seleccionar vehículo
2. Seleccionar tipo de asignación
3. Seleccionar conductor
4. Verificar documentos requeridos
5. Confirmar asignación
```

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test --filter CarrierRegistrationTest

# Con cobertura
php artisan test --coverage
```

### Estructura de Tests

```
tests/
├── Feature/          # Tests de integración
│   ├── CarrierRegistrationTest.php
│   ├── DriverApplicationTest.php
│   └── VehicleAssignmentTest.php
├── Unit/             # Tests unitarios
│   ├── Models/
│   └── Services/
└── TestCase.php
```

## 🚀 Deployment

### Preparación para Producción

1. **Optimizar Configuración**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **Compilar Assets**
```bash
npm run build
```

3. **Configurar Variables de Entorno**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
```

4. **Configurar Permisos**
```bash
chmod -R 755 storage bootstrap/cache
```

5. **Configurar Supervisor (Colas)**
```ini
[program:carrier-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

### Checklist de Deployment

- [ ] Variables de entorno configuradas
- [ ] Base de datos migrada
- [ ] Assets compilados
- [ ] Caché optimizado
- [ ] Colas configuradas
- [ ] Cron jobs configurados
- [ ] SSL configurado
- [ ] Backups configurados
- [ ] Monitoring configurado

## 🏗️ Arquitectura

### Stack Tecnológico

**Backend:**
- Laravel 11
- PHP 8.2+
- MySQL/PostgreSQL
- Redis

**Frontend:**
- Livewire 3.0
- Alpine.js 3.14
- Tailwind CSS 3.4
- Chart.js, FullCalendar

**Paquetes Principales:**
- Spatie Laravel Permission (roles)
- Spatie Laravel Media Library (archivos)
- Laravel Jetstream (autenticación)
- DomPDF (reportes PDF)
- Maatwebsite Excel (exportación)

### Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Controladores admin
│   │   ├── Carrier/        # Controladores carrier
│   │   ├── Driver/         # Controladores driver
│   │   └── Auth/           # Autenticación
│   ├── Middleware/         # Middleware personalizado
│   └── Requests/           # Form Requests
├── Models/                 # Modelos Eloquent
├── Services/               # Lógica de negocio
└── Repositories/           # Acceso a datos

resources/
├── views/                  # Vistas Blade
└── js/                     # JavaScript

routes/
├── web.php                 # Rutas web públicas
├── admin.php               # Rutas admin
├── carrier.php             # Rutas carrier
├── driver.php              # Rutas driver
├── api.php                 # API endpoints
└── debug.php               # Rutas debug (solo local)
```

### Patrones de Diseño

- **MVC**: Arquitectura base de Laravel
- **Repository Pattern**: Acceso a datos
- **Service Layer**: Lógica de negocio
- **Observer Pattern**: Eventos y listeners
- **Factory Pattern**: Creación de objetos

## 📚 Documentación Adicional

- [Análisis Completo del Proyecto](ANALISIS_PROYECTO.md)
- [Resumen Ejecutivo](RESUMEN_EJECUTIVO.md)
- [Arquitectura Visual](ARQUITECTURA_VISUAL.md)
- [Checklist de Mejoras](CHECKLIST_MEJORAS.md)
- [Especificaciones Técnicas](.kiro/specs/project-analysis/requirements.md)

### Documentación de Features

#### Driver Testing Module
- [Requisitos](.kiro/specs/driver-testing-improvements/requirements.md)
- [Diseño Técnico](.kiro/specs/driver-testing-improvements/design.md)
- [Plan de Implementación](.kiro/specs/driver-testing-improvements/tasks.md)

**Características Principales:**

1. **API Integration con Manejo Robusto de Errores**
   - Endpoint: `/api/active-drivers-by-carrier/{carrierId}`
   - Timeout de 10 segundos con AbortController
   - Diferenciación de errores (timeout, red, servidor)
   - Mensajes de error específicos para cada escenario
   - Manejo de listas vacías con advertencias

2. **Formateo Consistente de Datos**
   - Helper class: `App\Helpers\FormatHelper`
   - Métodos para formatear fechas (MM/DD/YYYY)
   - Formateo de nombres completos (First Middle Last)
   - Formateo de nombres de carriers con DOT
   - Clases CSS para badges de estado y resultados

3. **Vista Detallada Moderna (show.blade.php)**
   - Diseño card-based con jerarquía visual clara
   - Header con gradiente y badges de estado
   - Sección principal con detalles de prueba
   - Sidebar con información de carrier y conductor
   - Previsualización de PDF embebida
   - Quick actions para navegación rápida

4. **Sistema de Notificaciones Toast**
   - Notificaciones con iconos SVG
   - Auto-dismiss configurable
   - Botón de cierre manual
   - Animaciones suaves (slide-in/out)
   - Tipos: success, error, warning, info

5. **Optimización de Performance**
   - Caché de lista de carriers (30 minutos)
   - Caché de conductores por carrier (15 minutos)
   - Eager loading de relaciones
   - Índices de base de datos optimizados
   - Lazy loading de detalles de conductor

**Uso del Módulo:**

```php
// Formatear fecha
use App\Helpers\FormatHelper;

$formattedDate = FormatHelper::formatDate($testing->test_date);
// Output: "11/05/2025"

// Formatear nombre de conductor
$driverName = FormatHelper::formatDriverName($driver);
// Output: "John Michael Smith"

// Obtener clase de badge
$badgeClass = FormatHelper::getStatusBadgeClass('approved');
// Output: "bg-success text-white"
```

```javascript
// Inicializar formulario de testing
const form = new DriverTestingForm({
    isEditMode: true,
    currentDriverId: 123
});

// Cargar conductores por carrier
await form.loadDrivers(carrierId);

// Mostrar notificación
form.showSuccess('Driver loaded successfully');
form.showError('Failed to load drivers');
form.showWarning('No active drivers found');
```

**Estructura de Archivos:**

```
app/
├── Helpers/
│   └── FormatHelper.php              # Utilidades de formateo
├── Models/Admin/Driver/
│   └── DriverTesting.php             # Modelo con accessors
└── Http/Controllers/Admin/
    └── DriverTestingController.php   # Controlador con eager loading

resources/views/admin/driver-testings/
├── index.blade.php                   # Lista de pruebas
├── create.blade.php                  # Formulario de creación
├── edit.blade.php                    # Formulario de edición
└── show.blade.php                    # Vista detallada moderna

public/js/
└── driver-testing-form.js            # Clase para manejo de formularios

tests/
├── Unit/Helpers/
│   └── FormatHelperTest.php          # Tests unitarios
└── Feature/
    ├── Api/
    │   └── ActiveDriversByCarrierTest.php  # Tests de API
    └── DriverTestingFormFlowTest.php       # Tests de integración
```

## 🤝 Contribución

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Estándares de Código

- Seguir PSR-12 para PHP
- Usar Laravel Pint para formateo
- Escribir tests para nuevas features
- Documentar funciones públicas

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## 👥 Autores

- **Tu Nombre** - *Desarrollo Inicial* - Kuiraweb (https://github.com/tu-usuario)

## 🙏 Agradecimientos

- Laravel Framework
- Spatie Packages
- Tailwind CSS
- Comunidad Open Source

## 📞 Soporte

Para soporte y preguntas:
- Email: support@example.com
- Issues: [GitHub Issues](https://github.com/tu-usuario/carrier-management-system/issues)
- Documentación: [Wiki](https://github.com/tu-usuario/carrier-management-system/wiki)

---

**Desarrollado con ❤️ usando Laravel**
