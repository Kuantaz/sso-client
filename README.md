# 🏛️ MDS SSO Client

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-5.1%2B%20to%2012%2B-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)
[![SOAP](https://img.shields.io/badge/SOAP-Web%20Services-009639?style=flat-square)](https://www.php.net/manual/en/book.soap.php)

Cliente PHP optimizado para integración con el sistema de autenticación SSO del **Ministerio de Desarrollo Social de Chile**.

## ✨ Características

- 🔐 **Autenticación SSO completa** con servicios del **Ministerio de Desarrollo Social de Chile**
- 👥 **Gestión integral de usuarios** (crear, buscar, eliminar)
- 📱 **Type-safe** con PHP 7.4+ type hints
- 🎭 **Facade incluida** para Laravel
- ⚡ **Auto-discovery** para Laravel 5.5+
- 🔧 **Soporte nativo Laravel 12+**

## 📦 Instalación

### Composer

```bash
composer require kuantaz/sso-client
```

### Laravel Framework

#### 🆕 Laravel 12+

El paquete se registra **automáticamente** incluyendo el alias `Sso`. Si necesitas configuración manual:

```php
<?php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withAliases([
        'Sso' => Mds\SsoClient\Facades\Sso::class,
    ])
    ->create();
```

#### 📋 Laravel 5.1 - 11

Para versiones anteriores, registra manualmente:

```php
// config/app.php
'providers' => [
    // ...
    Mds\SsoClient\SsoServiceProvider::class,
],

'aliases' => [
    // ...
    'Sso' => Mds\SsoClient\Facades\Sso::class,
],
```

## ⚙️ Configuración

### 1. Publicar Configuración

```bash
php artisan vendor:publish --tag=sso-config
```

### 2. Variables de Entorno

Configura tu archivo `.env`:

```env
# Configuración SSO del MDS
SSO_WSDL=url_wsdl
SSO_AID=tu_application_id_aqui
SSO_ROL=tu_rol_por_defecto
```

### 3. Configuración Avanzada

Edita `config/sso.php` para configuración personalizada:

```php
<?php

return [
    'sso_wsdl' => env('SSO_WSDL'),
    'sso_aid' => env('SSO_AID'),
    'sso_rol' => env('SSO_ROL'),

    // Configuración SOAP personalizada
    'soap_options' => [
        'timeout' => 30,
        'connection_timeout' => 10,
    ],
];
```

## 🚀 Uso

### 📝 Ejemplos Básicos

#### Buscar Usuario por RUT

```php
use Mds\SsoClient\Sso;

$resultado = Sso::getUsuario('12345678-9');

if ($resultado->estado === 'ok') {
    echo "Usuario encontrado: " . $resultado->usuario->Usuarios->Usuario->Nombre;
} else {
    echo "Error: " . $resultado->estado_msg;
}
```

#### Autorizar Token

```php
use Mds\SsoClient\Sso;

$autorizacion = Sso::getAutorizar('token_aqui');

if ($autorizacion->estado === 'ok') {
    // Token válido - proceder con la aplicación
    $userInfo = $autorizacion->autorizar;
} else {
    // Token inválido - rechazar acceso
    return response()->json(['error' => $autorizacion->estado_msg], 401);
}
```

#### Crear Nuevo Usuario

```php
use Mds\SsoClient\Sso;

$resultado = Sso::setCrearUsuario(
    rut: '12345678-9',
    nombre: 'Juan Pérez',
    correo: 'juan@example.com',
    clave: 'password_seguro_123',
    habilitado: true
);

if ($resultado->estado === 'ok') {
    echo "Usuario creado exitosamente";
} else {
    echo "Error al crear usuario: " . $resultado->estado_msg;
}
```

#### Gestión de Roles

```php
use Mds\SsoClient\Sso;

// Asignar múltiples roles
$roles = [1, 2]; // Admin y Revisor
$resultado = Sso::setAsignarRoles('12345678-9', $roles);

// Listar roles de usuario
$rolesUsuario = Sso::listarRolesUsuario('12345678-9');

if ($rolesUsuario->estado === 'ok') {
    foreach ($rolesUsuario->usuario->Roles->Rol as $rol) {
        echo "Rol: " . $rol->Nombre . "\n";
    }
}
```

### 🔧 Uso Directo de Clase

```php
use Mds\SsoClient\Sso;

// Sin facade - útil para testing o uso fuera de Laravel
$usuario = Sso::getUsuario('12345678-9');
$resultado = Sso::eliminarUsuario('98765432-1');
```

### 🏗️ Uso Avanzado en Controladores

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mds\SsoClient\Sso;
use App\Models\User;
use Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $rut = $request->input('rut');
        $usuario = Sso::getUsuario($rut);

        if ($usuario->estado === 'ok') {
            $rut = $response->autorizar->Usuario->RUT;
            $runData = explode("-", $rut);
            $usuario = User::with('roles')->where('rut_numero', $runData[0])->first();
            // Crear sesión local
            $token = Auth::guard('web')->fromUser($usuario);

            return redirect()->route('dashboard');
        }

        return back()->withErrors(['rut' => 'Usuario no encontrado en SSO']);
    }

}
```

## 📋 API Reference

### Métodos Disponibles

| Método                                       | Descripción                   | Parámetros                     | Retorno  |
| -------------------------------------------- | ----------------------------- | ------------------------------ | -------- |
| `getUsuario(string $rut)`                    | Buscar usuario por RUT        | `$rut`: RUT del usuario        | `object` |
| `getAutorizar(string $token)`                | Validar token de autorización | `$token`: Token JWT/OAuth      | `object` |
| `setCrearUsuario(...)`                       | Crear nuevo usuario           | Ver parámetros abajo           | `object` |
| `setAsignarRoles(string $rut, array $roles)` | Asignar roles a usuario       | `$rut`, `$roles`: Array de IDs | `object` |
| `listarRolesUsuario(string $rut)`            | Listar roles de usuario       | `$rut`: RUT del usuario        | `object` |
| `eliminarUsuario(string $rut)`               | Eliminar usuario              | `$rut`: RUT del usuario        | `object` |

### Parámetros `setCrearUsuario()`

```php
Sso::setCrearUsuario(
    string $rut,           // RUT del usuario (ej: '12345678-9')
    string $nombre,        // Nombre completo
    string $correo,        // Email válido
    string $clave,         // Contraseña
    bool $habilitado = true // Estado del usuario
): object
```

### Estructura de Respuestas

#### ✅ Respuesta Exitosa

```php
$respuesta = (object) [
    'estado' => 'ok',
    'estado_msg' => 'Operación exitosa',
    'usuario' => (object) [
        // Datos específicos según la operación
        'Usuarios' => [...],
        'Cantidad' => 1
    ]
];
```

#### ❌ Respuesta de Error

```php
$respuesta = (object) [
    'estado' => 'error',
    'estado_c' => 0,
    'estado_msg' => 'Descripción del error'
];
```

## 🔧 Constantes Disponibles

```php
// Estados de respuesta
Sso::SSO_CODIGO_ESTADO_OK           // 1 - Operación exitosa
Sso::SSO_CODIGO_ESTADO_ERROR        // 0 - Error en operación
Sso::SSO_CODIGO_ESTADO_ADVERTENCIA  // 2 - Advertencia

// Mensajes de error
Sso::ERROR_SSO_WSDL                 // Error de configuración WSDL
Sso::ERROR_SSO_AID                  // Error de Application ID
Sso::ERROR_RUT_REQUERIDO            // RUT no proporcionado
// ... más constantes disponibles
```

## 📋 Requisitos del Sistema

- **PHP**: 7.4 o superior
- **Extensiones PHP**:
  - `ext-soap` (requerida)
  - `ext-json` (incluida por defecto)
- **Laravel**: 5.1+ hasta 12+ (opcional pero recomendado)
- **Composer**: Para gestión de dependencias

## 🧪 Testing

```bash
# Ejecutar tests
composer test

# Con cobertura
composer test:coverage

# Análisis estático
composer analyze
```

### Ejemplo de Test

```php
<?php

use Mds\SsoClient\Sso;
use PHPUnit\Framework\TestCase;

class SsoTest extends TestCase
{
    public function test_can_validate_user()
    {
        $result = Sso::getUsuario('12345678-9');
        $this->assertIsObject($result);
        $this->assertObjectHasAttribute('estado', $result);
    }
}
```

## 🚨 Manejo de Errores

### Errores Comunes y Soluciones

| Error                 | Causa                   | Solución                        |
| --------------------- | ----------------------- | ------------------------------- |
| `ERROR_SSO_WSDL`      | WSDL no configurado     | Configurar `SSO_WSDL` en `.env` |
| `ERROR_SSO_AID`       | Application ID inválido | Verificar `SSO_AID` con MDS     |
| `ERROR_RUT_REQUERIDO` | RUT no proporcionado    | Validar entrada antes de llamar |
| SOAP Exception        | Conexión/certificados   | Verificar conectividad y SSL    |

### Logging Personalizado

```php
use Illuminate\Support\Facades\Log;
use Mds\SsoClient\Sso;

$resultado = Sso::getUsuario('12345678-9');

if ($resultado->estado === 'error') {
    Log::error('SSO Error', [
        'message' => $resultado->estado_msg,
        'code' => $resultado->estado_c ?? null,
        'method' => 'getUsuario',
        'rut' => '12345678-9'
    ]);
}
```

## 🔄 Changelog

### v2.0.0 - 2025-06-02

- ✨ **Nueva**: Soporte completo Laravel 12
- 🚀 **Optimizado**: Refactorización completa con principios DRY
- 🛡️ **Mejorado**: 100% Type-safe con PHP 7.4+ type hints
- 🔧 **Agregado**: 7 métodos auxiliares para eliminar duplicación
- 📝 **Actualizado**: Documentación completa y ejemplos

Ver [CHANGELOG.md](CHANGELOG.md) para historial completo.

## 🤝 Contribuir

1. **Fork** el repositorio
2. Crea una **rama feature** (`git checkout -b feature/nueva-funcionalidad`)
3. **Commit** tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. **Push** a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un **Pull Request**

### Estándares de Código

- Seguir **PSR-12** para estilo de código
- **Type hints** obligatorios en métodos nuevos
- **Tests** para nueva funcionalidad
- **Documentación** actualizada

## 📄 Licencia

Este proyecto está licenciado bajo la [Licencia MIT](LICENSE).

## 👥 Créditos

- **Kuantaz** - _Optimización y mantenimiento_ - kuantaz@kuantaz.com
- **Ministerio de Desarrollo Social de Chile** - _Sistema SSO_

## 🆘 Soporte

### 📞 Canales de Soporte

- 🐛 **Issues**: [GitHub Issues](https://github.com/kuantaz/sso-client/issues)
- 📧 **Email**: kuantaz@kuantaz.com
- 📚 **Documentación**: Este README y código autodocumentado

### 🔍 Antes de Reportar Issues

1. Verifica que tu configuración sea correcta
2. Revisa los logs de Laravel/PHP
3. Comprueba conectividad con el servidor SSO
4. Busca en issues existentes

---

<p align="center">
Made with ❤️ by <a href="https://kuantaz.com">Kuantaz</a> for the Chilean Government
</p>
