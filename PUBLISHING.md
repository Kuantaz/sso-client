# Instrucciones para Publicar en Packagist

## Preparación

1. **Crear repositorio en GitHub:**

    ```bash
    # Desde la carpeta resources/package/
    git init
    git add .
    git commit -m "Initial commit"
    git branch -M main
    git remote add origin https://github.com/tu-usuario/mds-sso-client.git
    git push -u origin main
    ```

2. **Verificar estructura del paquete:**
    ```
    resources/package/
    ├── src/
    │   ├── Facades/
    │   │   └── Sso.php
    │   ├── Sso.php
    │   └── SsoServiceProvider.php
    ├── tests/
    │   └── SsoTest.php
    ├── config/
    │   └── sso.php
    ├── composer.json
    ├── README.md
    ├── LICENSE
    ├── CHANGELOG.md
    ├── phpunit.xml
    └── .gitignore
    ```

## Publicación en Packagist

1. **Ir a [Packagist.org](https://packagist.org/)**

2. **Crear cuenta o iniciar sesión**

3. **Hacer clic en "Submit"**

4. **Introducir la URL del repositorio:**

    ```
    https://github.com/tu-usuario/mds-sso-client
    ```

5. **Verificar que la información sea correcta**

6. **Configurar Auto-Update (recomendado):**
    - Ir a la configuración del paquete en Packagist
    - Configurar webhook para actualización automática

## Versionado

Para crear una nueva versión:

```bash
# Crear tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

## Instalación

Una vez publicado, se puede instalar con:

```bash
composer require mds/sso-client
```

## Verificación

Verificar que el paquete esté disponible en:

-   https://packagist.org/packages/mds/sso-client

## Notas Importantes

-   El nombre del paquete debe ser único en Packagist
-   Si el nombre `mds/sso-client` ya existe, cambiar en composer.json
-   Asegurase de que todas las pruebas pasen antes de publicar
-   Mantener el CHANGELOG.md actualizado con cada versión
