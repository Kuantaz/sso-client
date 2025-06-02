# MDS SSO Client

Cliente SSO para integración con el sistema de autenticación del Ministerio de Desarrollo Social de Chile.

## Instalación

Instalar el paquete usando Composer:

```bash
composer require kuantaz/sso-client
```

### Laravel

El paquete se registra automáticamente en Laravel 5.5+ gracias al autodiscovery.

#### Laravel 12+

En Laravel 12, el alias se registra automáticamente mediante el Service Provider. Si necesitas configurarlo manualmente, agrega el alias en tu archivo `bootstrap/app.php`:

```php
<?php

use Illuminate\Foundation\Application;

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

#### Laravel 5.1 - 11

Para versiones anteriores a Laravel 12, agrega el service provider y alias manualmente:

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

## Configuración

Publicar el archivo de configuración:

```bash
php artisan vendor:publish --tag=sso-config
```

Configurar las variables de entorno en tu archivo `.env`:

```env
SSO_WSDL=https://tu-servidor-sso.cl/ws/servicio.wsdl
SSO_AID=tu_application_id
SSO_ROL=tu_rol_por_defecto
```

## Uso

### Usando la Facade (Laravel)

```php
use Sso;

// Obtener usuario por RUT
$usuario = Sso::getUsuario('12345678-9');

// Autorizar token
$autorizacion = Sso::getAutorizar('token_aqui');

// Crear usuario
$resultado = Sso::setCrearUsuario(
    '12345678-9',
    'Juan Pérez',
    'juan@example.com',
    'password123'
);

// Asignar roles
$resultado = Sso::setAsignarRoles('12345678-9', [1, 2]);

// Listar roles de usuario
$roles = Sso::listarRolesUsuario('12345678-9');

// Eliminar usuario
$resultado = Sso::eliminarUsuario('12345678-9');
```

### Uso directo de la clase

```php
use Mds\SsoClient\Sso;

// Todas las funciones disponibles como métodos estáticos
$usuario = Sso::getUsuario('12345678-9');
```

## Constantes disponibles

```php
Sso::SSO_ROL_ADMIN      // 1
Sso::SSO_ROL_REVISOR    // 2
Sso::SSO_ROL_OPERADOR   // 3
Sso::SSO_CODIGO_ESTADO_OK     // 1
Sso::SSO_CODIGO_ESTADO_ERROR  // 0
```

## Respuestas

Todas las funciones retornan un objeto con la siguiente estructura:

```php
// Respuesta exitosa
$respuesta = (object) [
    'estado' => 'ok',
    'estado_msg' => 'Mensaje descriptivo',
    'data' => // datos específicos de la función
];

// Respuesta de error
$respuesta = (object) [
    'estado' => 'error',
    'estado_c' => 0,
    'estado_msg' => 'Mensaje de error'
];
```

## Requisitos

- PHP >= 7.4
- Extensión SOAP de PHP
- Laravel 5.1+ (opcional, para usar con Laravel)

## Testing

```bash
composer test
```

## Changelog

Consulta [CHANGELOG.md](CHANGELOG.md) para ver los cambios recientes.

## Licencia

Este paquete es open-source bajo la [Licencia MIT](LICENSE).

## Créditos

- **Fabián Aravena O.** - _Autor original_ - faravena@desarrollosocial.cl
- Ministerio de Desarrollo Social de Chile

## Soporte

Para problemas o consultas, por favor crea un issue en el repositorio de GitHub.
