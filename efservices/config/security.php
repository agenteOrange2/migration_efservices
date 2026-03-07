<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad para el sistema de gestión de transportistas.
    | Estas configuraciones ayudan a proteger la aplicación contra amenazas comunes.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuración de límites de tasa para diferentes tipos de requests.
    |
    */
    'rate_limiting' => [
        'api' => [
            'max_attempts' => env('API_RATE_LIMIT', 60),
            'decay_minutes' => env('API_RATE_DECAY', 1),
        ],
        'auth' => [
            'max_attempts' => env('AUTH_RATE_LIMIT', 5),
            'decay_minutes' => env('AUTH_RATE_DECAY', 1),
        ],
        'upload' => [
            'max_attempts' => env('UPLOAD_RATE_LIMIT', 20),
            'decay_minutes' => env('UPLOAD_RATE_DECAY', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad para carga de archivos.
    |
    */
    'uploads' => [
        'max_file_size' => env('MAX_FILE_SIZE', 10240), // KB (10MB default)
        'allowed_mimes' => [
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'all' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp'],
        ],
        'scan_for_viruses' => env('SCAN_UPLOADS', false),
        'sanitize_filenames' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    |
    | Tiempo de expiración para diferentes tipos de tokens (en minutos).
    |
    */
    'tokens' => [
        'email_verification' => env('EMAIL_VERIFICATION_EXPIRY', 1440), // 24 horas
        'vehicle_verification' => env('VEHICLE_VERIFICATION_EXPIRY', 10080), // 7 días
        'employment_verification' => env('EMPLOYMENT_VERIFICATION_EXPIRY', 10080), // 7 días
        'password_reset' => env('PASSWORD_RESET_EXPIRY', 60), // 1 hora
        'referrer_token' => env('REFERRER_TOKEN_EXPIRY', 43200), // 30 días
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad para sesiones.
    |
    */
    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 120), // minutos
        'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
        'encrypt' => env('SESSION_ENCRYPT', false),
        'secure' => env('SESSION_SECURE_COOKIE', false), // true en producción con HTTPS
        'same_site' => env('SESSION_SAME_SITE', 'lax'), // lax, strict, none
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    |
    | Requisitos de contraseña para usuarios.
    |
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL', false),
        'prevent_common' => env('PASSWORD_PREVENT_COMMON', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Configuración de autenticación de dos factores.
    |
    */
    '2fa' => [
        'enabled' => env('2FA_ENABLED', false),
        'required_for_admin' => env('2FA_REQUIRED_ADMIN', false),
        'grace_period' => env('2FA_GRACE_PERIOD', 7), // días
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist/Blacklist
    |--------------------------------------------------------------------------
    |
    | Listas de IPs permitidas o bloqueadas.
    |
    */
    'ip_filtering' => [
        'enabled' => env('IP_FILTERING_ENABLED', false),
        'whitelist' => explode(',', env('IP_WHITELIST', '')),
        'blacklist' => explode(',', env('IP_BLACKLIST', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Headers de seguridad HTTP.
    |
    */
    'headers' => [
        'x_frame_options' => env('X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        'strict_transport_security' => env('STRICT_TRANSPORT_SECURITY', 'max-age=31536000; includeSubDomains'),
        'content_security_policy' => env('CONTENT_SECURITY_POLICY', "default-src 'self'"),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuración de logging de auditoría.
    |
    */
    'audit' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'log_channel' => env('AUDIT_LOG_CHANNEL', 'daily'),
        'events' => [
            'user_login' => true,
            'user_logout' => true,
            'user_created' => true,
            'user_updated' => true,
            'user_deleted' => true,
            'carrier_created' => true,
            'carrier_approved' => true,
            'driver_created' => true,
            'driver_approved' => true,
            'vehicle_assigned' => true,
            'document_uploaded' => true,
            'document_approved' => true,
            'document_rejected' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Login Attempts
    |--------------------------------------------------------------------------
    |
    | Configuración para intentos fallidos de login.
    |
    */
    'failed_logins' => [
        'max_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('LOGIN_LOCKOUT_DURATION', 15), // minutos
        'notify_admin' => env('NOTIFY_ADMIN_FAILED_LOGINS', true),
        'threshold_for_notification' => env('FAILED_LOGIN_NOTIFICATION_THRESHOLD', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de Cross-Origin Resource Sharing.
    |
    */
    'cors' => [
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
        'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', '*')),
        'exposed_headers' => explode(',', env('CORS_EXPOSED_HEADERS', '')),
        'max_age' => env('CORS_MAX_AGE', 0),
        'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad para base de datos.
    |
    */
    'database' => [
        'encrypt_sensitive_data' => env('DB_ENCRYPT_SENSITIVE', false),
        'backup_enabled' => env('DB_BACKUP_ENABLED', true),
        'backup_frequency' => env('DB_BACKUP_FREQUENCY', 'daily'), // daily, weekly, monthly
    ],

];
