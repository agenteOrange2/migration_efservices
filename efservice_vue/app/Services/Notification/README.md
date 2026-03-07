# Notification Services - Refactorización

## Descripción

El `NotificationService` original (844 líneas) ha sido dividido en servicios especializados para mejorar la mantenibilidad, testabilidad y seguir el principio de responsabilidad única (SRP).

## Estructura de Servicios

### 1. EmailNotificationService
**Responsabilidad:** Envío de notificaciones por email

**Métodos principales:**
- `sendCarrierRegistrationEmail()` - Enviar emails de registro de carrier
- `sendNotificationEmail()` - Enviar email genérico
- `sendBulkEmails()` - Enviar emails masivos

**Ejemplo de uso:**
```php
$emailService = app(EmailNotificationService::class);
$emailService->sendNotificationEmail(
    $user,
    'Welcome!',
    'Thank you for registering'
);
```

### 2. DatabaseNotificationService
**Responsabilidad:** Gestión de notificaciones en base de datos

**Métodos principales:**
- `createNotification()` - Crear notificación para usuario
- `createNotificationForMultipleUsers()` - Crear para múltiples usuarios
- `markAsRead()` - Marcar como leída
- `getUnreadNotifications()` - Obtener no leídas
- `deleteNotification()` - Eliminar notificación

**Ejemplo de uso:**
```php
$dbService = app(DatabaseNotificationService::class);
$dbService->createNotification(
    $user,
    'document_uploaded',
    'Your document has been uploaded'
);
```

### 3. NotificationPreferenceService
**Responsabilidad:** Gestión de preferencias de notificación

**Métodos principales:**
- `isNotificationEnabled()` - Verificar si notificación está habilitada
- `getPreferencesForUser()` - Obtener preferencias de usuario
- `updatePreference()` - Actualizar preferencia
- `createDefaultPreferences()` - Crear preferencias por defecto
- `getCategoriesForRole()` - Obtener categorías por rol

**Ejemplo de uso:**
```php
$prefService = app(NotificationPreferenceService::class);
$isEnabled = $prefService->isNotificationEnabled(
    $user,
    'carrier_registration',
    'email'
);
```

### 4. NotificationLogService
**Responsabilidad:** Logging y estadísticas de notificaciones

**Métodos principales:**
- `log()` - Registrar intento de envío
- `logSuccess()` - Registrar éxito
- `logFailure()` - Registrar fallo
- `getNotificationLogs()` - Obtener logs con filtros
- `getNotificationStats()` - Obtener estadísticas
- `retryFailedNotification()` - Reintentar notificación fallida

**Ejemplo de uso:**
```php
$logService = app(NotificationLogService::class);
$logService->logSuccess(
    $user,
    'email_sent',
    'email',
    ['recipient' => $user->email]
);
```

### 5. NotificationServiceRefactored (Orquestador)
**Responsabilidad:** Coordinar entre todos los servicios especializados

Este servicio actúa como fachada y orquesta las operaciones que requieren múltiples servicios.

**Ejemplo de uso:**
```php
$notificationService = app(NotificationServiceRefactored::class);

// Envía notificación respetando preferencias del usuario
$notificationService->sendWithPreferences(
    $user,
    new WelcomeNotification(),
    'general'
);

// Notifica a admins sobre nuevo carrier
$notificationService->notifyAdminsOfNewCarrier(
    $newUser,
    'New carrier registered: ' . $carrier->name
);
```

## Migración del Código Existente

### Antes (NotificationService original):
```php
$notificationService = app(NotificationService::class);
$notificationService->createNotification($user, 'type', 'message');
```

### Después (Opción 1 - Servicio específico):
```php
$dbService = app(DatabaseNotificationService::class);
$dbService->createNotification($user, 'type', 'message');
```

### Después (Opción 2 - Orquestador):
```php
$notificationService = app(NotificationServiceRefactored::class);
$notificationService->createNotification($user, 'type', 'message');
```

## Beneficios de la Refactorización

### 1. Mantenibilidad
- Cada servicio tiene una responsabilidad única
- Archivos más pequeños (~100-150 líneas vs 844 líneas)
- Más fácil de entender y modificar

### 2. Testabilidad
- Cada servicio se puede testear independientemente
- Mocking más sencillo
- Tests más específicos y rápidos

### 3. Extensibilidad
- Fácil agregar nuevos canales (SMS, Push, Slack, etc.)
- Fácil agregar nuevas funcionalidades sin afectar otros servicios

### 4. Reutilización
- Los servicios especializados se pueden usar independientemente
- No necesitas cargar todo el servicio si solo necesitas una funcionalidad

## Pasos para Implementación Completa

1. **Registrar servicios en Service Provider:**
```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(EmailNotificationService::class);
    $this->app->singleton(DatabaseNotificationService::class);
    $this->app->singleton(NotificationPreferenceService::class);
    $this->app->singleton(NotificationLogService::class);
    $this->app->singleton(NotificationServiceRefactored::class);
}
```

2. **Crear alias para backward compatibility:**
```php
// config/app.php
'aliases' => [
    // ...
    'NotificationService' => App\Services\NotificationServiceRefactored::class,
]
```

3. **Migrar código existente progresivamente:**
   - Identificar usos de `NotificationService`
   - Reemplazar con servicios especializados o el orquestador
   - Escribir tests para validar comportamiento

4. **Eventualmente deprecar NotificationService original:**
```php
/**
 * @deprecated Use NotificationServiceRefactored or specialized services instead
 */
class NotificationService { }
```

## Testing

### Ejemplo de test para DatabaseNotificationService:
```php
class DatabaseNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_notification_for_user()
    {
        $user = User::factory()->create();
        $service = new DatabaseNotificationService();

        $notification = $service->createNotification(
            $user,
            'test_type',
            'Test message'
        );

        $this->assertNotNull($notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('Test message', $notification->message);
    }
}
```

## Métricas de Mejora

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Líneas por archivo | 843 | ~100-150 | ~80% reducción |
| Métodos por clase | 27 | ~5-8 | ~70% reducción |
| Responsabilidades | Múltiples | 1 por servicio | SRP cumplido |
| Testabilidad | Difícil | Fácil | 100% mejora |

## Notas Adicionales

- El `NotificationService` original se mantiene por compatibilidad
- Se recomienda migrar gradualmente al nuevo sistema
- Todos los servicios usan inyección de dependencias
- Logging y error handling incluidos en todos los servicios
