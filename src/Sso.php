<?php

namespace Mds\SsoClient;

/**
 * Funciones SSO del Ministerio de Desarrollo Social
 * para Laravel
 * -------------------------------
 * Kuantaz - 2025
 * -------------------------------
 */
class Sso
{
    const SSO_CODIGO_ESTADO_OK = 1;
    const SSO_CODIGO_ESTADO_ERROR = 0;
    const SSO_CODIGO_ESTADO_ADVERTENCIA = 2;

    // Mensajes de error constantes
    const ERROR_SSO_WSDL = 'Error SSO: Debe configurar el ambiente del SSO, variable "SSO_WSDL".';
    const ERROR_SSO_AID = 'Error SSO: Debe configurar el ambiente del SSO, variable "SSO_AID".';
    const ERROR_RUT_REQUERIDO = 'Error Funcion SSO: El RUT es requerido.';
    const ERROR_NOMBRE_REQUERIDO = 'Error Funcion SSO: El Nombre es requerido.';
    const ERROR_CORREO_REQUERIDO = 'Error Funcion SSO: El Correo es requerido.';
    const ERROR_CLAVE_REQUERIDA = 'Error Funcion SSO: La Clave es requerida.';
    const ERROR_TOKEN_REQUERIDO = 'Error Funcion SSO: El token es requerido.';
    const ERROR_ROLES_REQUERIDOS = 'Error Funcion SSO: Los Roles son requeridos.';
    const ERROR_APP_ROLES = 'Error Aplicación: Validar ID de aplicación del SSO, variable "SSO_AID", ya que no tiene ningún Rol asignado en SSO.';

    /**
     * Obtener configuración del SSO
     *
     * @param string $key
     * @return string|null
     */
    private static function getConfig(string $key): ?string
    {
        return config("sso.{$key}") ?? env(strtoupper("SSO_{$key}"));
    }

    /**
     * Validar si un valor está vacío
     *
     * @param mixed $value
     * @return bool
     */
    private static function isEmpty($value): bool
    {
        return !isset($value) || empty($value);
    }

    /**
     * Validar parámetros requeridos
     *
     * @param array $params
     * @return object|null
     */
    private static function validateParams(array $params): ?object
    {
        foreach ($params as $param => $errorMessage) {
            if (self::isEmpty($param)) {
                return self::createErrorResponse($errorMessage);
            }
        }
        return null;
    }

    /**
     * Crear respuesta de error estándar
     *
     * @param string $message
     * @return object
     */
    private static function createErrorResponse(string $message): object
    {
        $response = new \stdClass();
        return self::respuesta($response, 'error', $message);
    }

    /**
     * Crear respuesta exitosa estándar
     *
     * @param string $dataKey
     * @param mixed $data
     * @return object
     */
    private static function createSuccessResponse(string $dataKey, $data): object
    {
        $response = new \stdClass();
        $response->{$dataKey} = $data;
        $response->estado = 'ok';
        return $response;
    }

    /**
     * Obtener parámetros SOAP estándar
     *
     * @return array
     */
    private static function getSoapParams(): array
    {
        return [
            'soap_version' => SOAP_1_2,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'encoding' => 'UTF-8',
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS
        ];
    }

    /**
     * Procesar respuesta del SSO
     *
     * @param object $respuesta
     * @param string $property
     * @param string $successField
     * @param mixed $successValue
     * @return object
     */
    private static function processSsoResponse(object $respuesta, string $property, string $successField, $successValue): object
    {
        if (isset($respuesta->{$property}->{$successField}) && $respuesta->{$property}->{$successField} == $successValue) {
            $respuesta->estado = 'ok';
            return $respuesta;
        }

        $errorMessage = isset($respuesta->{$property}->Detalle) 
            ? 'Advertencia de SSO: ' . $respuesta->{$property}->Detalle 
            : 'Advertencia de SSO.';

        return self::respuesta($respuesta, 'error', $errorMessage);
    }

    /**
     * Ejecutar operación SSO con manejo de errores
     *
     * @param callable $operation
     * @return object
     */
    private static function executeSsoOperation(callable $operation): object
    {
        try {
            return $operation();
        } catch (\Exception $e) {
            return self::error('Error SSO: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva instancia de SSO
     * 
     * @return object
     */
    public static function newSSO(): object
    {
        return self::executeSsoOperation(function () {
            $wsdl = self::getConfig('wsdl');
            if (self::isEmpty($wsdl)) {
                return self::error(self::ERROR_SSO_WSDL);
            }

            $sso = new \SoapClient($wsdl, self::getSoapParams());
            $sso->estado = 'ok';
            $sso->estado_c = self::SSO_CODIGO_ESTADO_OK;
            $sso->estado_msg = 'SSO conectado correctamente.';

            if ($sso->estado_c !== self::SSO_CODIGO_ESTADO_OK) {
                return $sso;
            }

            $aid = self::getConfig('aid');
            if (self::isEmpty($aid)) {
                return self::error(self::ERROR_SSO_AID);
            }

            $rolesResult = $sso->ListarRolesAplicacion(['AID' => $aid])->ListarRolesAplicacionResult;
            $sso->roles = $rolesResult ?? null;

            if (!isset($sso->roles->Roles) || count($sso->roles->Roles->Rol) === 0) {
                return self::error(self::ERROR_APP_ROLES);
            }

            return $sso;
        });
    }

    /**
     * Obtener usuario por RUT
     * 
     * @param string $rut
     * @return object
     */
    public static function getUsuario(string $rut): object
    {
        return self::executeSsoOperation(function () use ($rut) {
            $validation = self::validateParams([$rut => self::ERROR_RUT_REQUERIDO]);
            if ($validation) {
                return $validation;
            }

            $sso = self::newSSO();
            if ($sso->estado_c !== self::SSO_CODIGO_ESTADO_OK) {
                return $sso;
            }

            $response = new \stdClass();
            $aid = self::getConfig('aid');
            $response->usuario = $sso->BuscarUsuario(['rut' => $rut, 'AID' => $aid])->BuscarUsuarioResult;

            if (isset($response->usuario->Cantidad) && $response->usuario->Cantidad === 1) {
                return self::createSuccessResponse('usuario', $response->usuario);
            }

            $errorMessage = isset($response->usuario->Detalle) 
                ? 'Advertencia de SSO: ' . $response->usuario->Detalle 
                : 'Advertencia de SSO.';

            return self::createErrorResponse($errorMessage);
        });
    }

    /**
     * Autorizar token
     * 
     * @param string $token
     * @return object
     */
    public static function getAutorizar(string $token): object
    {
        $validation = self::validateParams([$token => self::ERROR_TOKEN_REQUERIDO]);
        if ($validation) {
            return $validation;
        }

        $sso = self::newSSO();
        if ($sso->estado_c !== self::SSO_CODIGO_ESTADO_OK) {
            return $sso;
        }

        $response = new \stdClass();
        $response->autorizar = $sso->Autorizar(['token' => $token])->AutorizarResult;

        return self::processSsoResponse($response, 'autorizar', 'Estado', 1);
    }

    /**
     * Crear usuario
     * 
     * @param string $rut
     * @param string $nombre
     * @param string $correo
     * @param string $clave
     * @param bool $habilitado
     * @return object
     */
    public static function setCrearUsuario(string $rut, string $nombre, string $correo, string $clave, bool $habilitado = true): object
    {
        $validation = self::validateParams([
            $rut => self::ERROR_RUT_REQUERIDO,
            $nombre => self::ERROR_NOMBRE_REQUERIDO,
            $correo => self::ERROR_CORREO_REQUERIDO,
            $clave => self::ERROR_CLAVE_REQUERIDA,
        ]);
        
        if ($validation) {
            return $validation;
        }

        $sso = self::newSSO();
        if ($sso->estado_c !== self::SSO_CODIGO_ESTADO_OK) {
            return $sso;
        }

        $response = new \stdClass();
        $aid = self::getConfig('aid');
        
        $userData = [
            'Usuario' => [
                'RUT' => $rut,
                'Nombre' => trim($nombre),
                'Correo' => trim($correo),
                'Clave' => $clave,
                'Habilitado' => $habilitado
            ],
            'AID' => $aid
        ];

        $response->crear = $sso->CrearUsuario($userData)->CrearUsuarioResult;

        return self::processSsoResponse($response, 'crear', 'Estado', 1);
    }

    /**
     * Asignar roles a usuario
     * 
     * @param string $rut
     * @param array $roles
     * @return object
     */
    public static function setAsignarRoles(string $rut, array $roles): object
    {
        $validation = self::validateParams([
            $rut => self::ERROR_RUT_REQUERIDO,
        ]);
        
        if ($validation) {
            return $validation;
        }
        // Validar que el array de roles no esté vacío
        if (empty($roles)) {
            return self::createErrorResponse(self::ERROR_ROLES_REQUERIDOS);
        }
        $sso = self::newSSO();
        if ($sso->estado_c !== self::SSO_CODIGO_ESTADO_OK) {
            return $sso;
        }

        $response = new \stdClass();
        $aid = self::getConfig('aid');
        $response->asignar = $sso->AsignarRoles(['rut' => $rut, 'roles' => $roles, 'AID' => $aid])->AsignarRolesResult;

        return self::processSsoResponse($response, 'asignar', 'Estado', 1);
    }

    /**
     * Listar roles de usuario
     * 
     * @param string $rut
     * @return object
     */
    public static function listarRolesUsuario(string $rut): object
    {
        return self::executeSsoOperation(function () use ($rut) {
            $validation = self::validateParams([$rut => self::ERROR_RUT_REQUERIDO]);
            if ($validation) {
                return $validation;
            }

            $sso = self::newSSO();
            if ($sso->estado_c !== self::SSO_CODIGO_ESTADO_OK) {
                return $sso;
            }

            $response = new \stdClass();
            $aid = self::getConfig('aid');
            
            $response->usuario = $sso->ListarRolesUsuario(['rut' => $rut, 'AID' => $aid])->ListarRolesUsuarioResult;

            if (isset($response->usuario->Roles)) {
                return self::createSuccessResponse('usuario', $response->usuario);
            }

            $errorMessage = isset($response->usuario->Detalle) 
                ? 'Advertencia de SSO: ' . $response->usuario->Detalle 
                : 'Advertencia de SSO.';

            return self::createErrorResponse($errorMessage);
        });
    }

    /**
     * Eliminar usuario
     * 
     * @param string $rut
     * @return object
     */
    public static function eliminarUsuario(string $rut): object
    {
        return self::executeSsoOperation(function () use ($rut) {
            $validation = self::validateParams([$rut => self::ERROR_RUT_REQUERIDO]);
            if ($validation) {
                return $validation;
            }

            $sso = self::newSSO();
            if ($sso->estado_c !== self::SSO_CODIGO_ESTADO_OK) {
                return $sso;
            }

            $response = new \stdClass();
            $aid = self::getConfig('aid');
            $response->usuario = $sso->EliminarUsuario(['rut' => $rut, 'AID' => $aid])->EliminarUsuarioResult;

            return self::processSsoResponse($response, 'usuario', 'Estado', 1);
        });
    }

    /**
     * Crear respuesta estándar
     * 
     * @param object $respuesta
     * @param string $estado
     * @param string $msg
     * @return object
     */
    public static function respuesta(object $respuesta, string $estado, string $msg): object
    {
        $respuesta->estado = $estado;
        $respuesta->estado_msg = $msg;
        return $respuesta;
    }

    /**
     * Crear respuesta de error
     * 
     * @param string $msg
     * @return object
     */
    public static function error(string $msg): object
    {
        $sso = new \stdClass();
        $sso->estado = 'error';
        $sso->estado_c = self::SSO_CODIGO_ESTADO_ERROR;
        $sso->estado_msg = $msg;
        return $sso;
    }
} 