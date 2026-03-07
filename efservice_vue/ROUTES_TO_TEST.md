# Rutas para probar la migración

Usa como base la URL de tu app (ej: `http://localhost`, `http://localhost/efservice_vue/public`, o `http://efervice_vue.test`).

---

## 1. Página de inicio (pública)

| Ruta | URL completa (ejemplo) | Requiere |
|------|------------------------|----------|
| Home | `/` | Nadie |

---

## 2. Login / Registro (Fortify)

| Ruta | URL | Requiere |
|------|-----|----------|
| Login | `/login` | Guest |
| Registro (si está habilitado) | `/register` | Guest |

---

## 3. Admin Dashboard (superadmin)

**Requisito:** estar logueado con usuario que tenga rol **superadmin**.

| Ruta | URL | Requiere |
|------|-----|----------|
| Dashboard admin | `/admin` o `/admin/` | Auth + rol superadmin |

Si entras sin estar logueado → redirige a `/login`.  
Si estás logueado con otro rol (carrier/driver) → redirige a su dashboard o login.

---

## 4. Carrier – Wizard de registro (Fase 2.1)

### Sin estar logueado (guest)

| Paso | URL | Requiere |
|------|-----|----------|
| Paso 1 – Datos básicos | `/carrier/wizard/step1` | Guest |
| Redirigir “registro carrier” | `/carrier/register` | Guest (redirige a step1) |

### Logueado como user_carrier (después de completar paso 1 y hacer login)

| Paso | URL | Requiere |
|------|-----|----------|
| Paso 2 – Empresa | `/carrier/wizard/step2` | Auth + user_carrier |
| Paso 3 – Membresía | `/carrier/wizard/step3` | Auth + user_carrier |
| Paso 4 – Datos bancarios | `/carrier/wizard/step4` | Auth + user_carrier |

### Páginas de estado (logueado como user_carrier)

| Página | URL | Requiere |
|--------|-----|----------|
| Pendiente de validación | `/carrier/pending-validation` | Auth + user_carrier |
| Confirmación | `/carrier/confirmation` | Auth + user_carrier |
| Cuenta inactiva | `/carrier/inactive` | Auth + user_carrier |
| Banking rechazado | `/carrier/banking-rejected` | Auth + user_carrier |

---

## 5. Driver – Registro (Fase 2.2)

Todas estas rutas son **públicas (guest)**.

| Página | URL | Requiere |
|--------|-----|----------|
| Seleccionar carrier | `/driver/register` | Guest |
| Formulario de registro (carrier elegido) | `/driver/register/form/{slug}` | Guest. Ej: `/driver/register/form/mi-transportista` |
| Registro por enlace de carrier (con token) | `/driver/driver-register/{slug}` | Guest |
| Éxito | `/driver/registration/success` | Guest (normalmente tras registro) |
| Error | `/driver/error` | Guest |
| Cuota superada | `/driver/quota-exceeded` | Guest |
| Estado carrier | `/driver/driver-status` | Guest |

---

## Resumen rápido para probar

1. **Home:**  
   `http://TU_BASE/`

2. **Login:**  
   `http://TU_BASE/login`

3. **Admin Dashboard** (solo si tienes usuario superadmin):  
   `http://TU_BASE/admin`

4. **Registro Carrier – Paso 1:**  
   `http://TU_BASE/carrier/wizard/step1`

5. **Registro Driver – Seleccionar carrier:**  
   `http://TU_BASE/driver/register`

---

## Si no ves la app

- Comprueba que el servidor esté levantado desde la raíz del proyecto **efservice_vue** (por ejemplo `php artisan serve` → `http://127.0.0.1:8000`).
- Si usas XAMPP con subcarpeta, la base suele ser:  
  `http://localhost/efservice_vue/public`  
  (o el virtual host que tengas en `APP_URL` del `.env`).
- Para Admin Dashboard hace falta un usuario con rol **superadmin**; si no existe, créalo y asígnale ese rol (p. ej. con un seeder o tinker).
