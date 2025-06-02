# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere al [Versionado Semántico](https://semver.org/lang/es/).

## [Sin publicar]

## [1.0.0] - 2025-06-02

### Agregado

-   Implementación inicial del cliente SSO
-   Soporte para todas las funciones del SSO original:
    -   `getUsuario()` - Obtener usuario por RUT
    -   `getAutorizar()` - Autorizar token
    -   `setCrearUsuario()` - Crear nuevo usuario
    -   `setAsignarRoles()` - Asignar roles a usuario
    -   `listarRolesUsuario()` - Listar roles de usuario
    -   `eliminarUsuario()` - Eliminar usuario
-   Service Provider para Laravel
-   Facade para uso fácil en Laravel
-   Archivo de configuración
-   Tests básicos
-   Documentación completa

### Seguridad

-   Mantenidas todas las validaciones de seguridad originales
-   Agregada validación adicional de parámetros
