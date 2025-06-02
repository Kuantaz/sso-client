<?php

namespace Mds\SsoClient;

/**
 * Funciones SSO del Ministerio de Desarrollo Social
 * para Laravel
 * -------------------------------
 * Fabián Aravena O. - faravena@desarrollosocial.cl
 * 2015-11-13
 * -------------------------------
 */
class Sso
{
    const SSO_ROL_ADMIN = 1;
    const SSO_ROL_REVISOR = 2;
    const SSO_ROL_OPERADOR = 3;
    const SSO_CODIGO_ESTADO_OK = 1;
    const SSO_CODIGO_ESTADO_ERROR = 0;

    /**
     * Crear nueva instancia de SSO
     * 
     * @return object
     */
    public static function newSSO()
    {
        $SSO_WSDL = config('sso.sso_wsdl') ?? env('SSO_WSDL');
        if (isset($SSO_WSDL) && !empty($SSO_WSDL)) {
            try {
                $params = array(
                    'soap_version' => SOAP_1_2,
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                    'encoding' => 'UTF-8',
                    'trace' => 1,
                    'exceptions' => true,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'features' => SOAP_SINGLE_ELEMENT_ARRAYS
                );
                $sso = new \SoapClient($SSO_WSDL, $params);
                $sso->estado = 'ok';
                $sso->estado_c = self::SSO_CODIGO_ESTADO_OK;
                $sso->estado_msg = 'SSO conectado correctamente.';
            } catch (\Exception $e) {
                return self::error('Error SSO: ' . $e->getMessage());
            }
            
            if ($sso->estado_c == self::SSO_CODIGO_ESTADO_OK) {
                $AID = config('sso.sso_aid') ?? env('SSO_AID');
                if (isset($AID) && !empty($AID)) {
                    $sso->roles = isset($sso->ListarRolesAplicacion(array('AID' => $AID))->ListarRolesAplicacionResult) 
                        ? $sso->ListarRolesAplicacion(array('AID' => $AID))->ListarRolesAplicacionResult 
                        : null;
                    if (isset($sso->roles->Roles) || count($sso->roles->Roles->Rol) > 0) {
                        return $sso;
                    }
                    return self::error('Error Aplicación: Validar ID de aplicación del SSO, variable "SSO_AID", ya que no tiene ningún Rol asignado en SSO.');
                }
                return self::error('Error SSO: Debe configurar el ambiente del SSO, variable "SSO_AID".');
            }
        }
        return self::error('Error SSO: Debe configurar el ambiente del SSO, variable "SSO_WSDL".');
    }

    /**
     * Obtener usuario por RUT
     * 
     * @param string $rut
     * @return object
     */
    public static function getUsuario($rut)
    {
        try {
            $respuesta = new \stdClass();
            if (isset($rut) && !empty($rut)) {
                $sso = self::newSSO();
                if ($sso->estado_c == self::SSO_CODIGO_ESTADO_OK) {
                    $AID = config('sso.sso_aid') ?? env('SSO_AID');
                    $respuesta->usuario = $sso->BuscarUsuario(array('rut' => $rut, 'AID' => $AID))->BuscarUsuarioResult;
                    if (isset($respuesta->usuario->Cantidad) && $respuesta->usuario->Cantidad == 1) {
                        $respuesta->estado = 'ok';
                        return $respuesta;
                    }
                } else {
                    return $sso;
                }
                return self::respuesta($respuesta, 'error', isset($respuesta->usuario->Detalle) ? 'Advertencia de SSO: ' . $respuesta->usuario->Detalle : 'Advertencia de SSO.');
            }
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El RUT es requerido.');
        } catch (\Exception $e) {
            return self::error('Error SSO: ' . $e->getMessage());
        }
    }

    /**
     * Autorizar token
     * 
     * @param string $token
     * @return object
     */
    public static function getAutorizar($token)
    {
        $respuesta = new \stdClass();
        if (isset($token) && !empty($token)) {
            $sso = self::newSSO();
            $respuesta->autorizar = $sso->Autorizar(array('token' => $token))->AutorizarResult;
            if (isset($respuesta->autorizar->Estado) && $respuesta->autorizar->Estado == 1) {
                $respuesta->estado = 'ok';
                return $respuesta;
            }
            return self::respuesta($respuesta, 'error', isset($respuesta->autorizar->Detalle) ? 'Advertencia de SSO: ' . $respuesta->autorizar->Detalle : 'Advertencia de SSO.');
        }
        return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El token es requerido.');
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
    public static function setCrearUsuario($rut, $nombre, $correo, $clave, $habilitado = true)
    {
        $respuesta = new \stdClass();
        if (!isset($rut) || empty($rut)) {
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El RUT es requerido.');
        }
        if (!isset($nombre) || empty($nombre)) {
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El Nombre es requerido.');
        }
        if (!isset($correo) || empty($correo)) {
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El Correo es requerido.');
        }
        if (!isset($clave) || empty($clave)) {
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: La Clave es requerida.');
        }
        
        $sso = self::newSSO();
        if ($sso->estado_c == self::SSO_CODIGO_ESTADO_OK) {
            $AID = config('sso.sso_aid') ?? env('SSO_AID');
            $respuesta->crear = $sso->CrearUsuario(array(
                'Usuario' => array(
                    'RUT' => $rut,
                    'Nombre' => trim($nombre),
                    'Correo' => trim($correo),
                    'Clave' => $clave,
                    'Habilitado' => $habilitado
                ),
                'AID' => $AID
            ))->CrearUsuarioResult;
            
            if (isset($respuesta->crear->Estado) && $respuesta->crear->Estado == 1) {
                $respuesta->estado = 'ok';
                return $respuesta;
            }
        } else {
            return $sso;
        }
        
        return self::respuesta(
            $respuesta,
            'error',
            isset($respuesta->crear->Detalle) ?
                'Advertencia de SSO: ' . $respuesta->crear->Detalle :
                'Advertencia de SSO.'
        );
    }

    /**
     * Asignar roles a usuario
     * 
     * @param string $rut
     * @param array $roles
     * @return object
     */
    public static function setAsignarRoles($rut, $roles = array())
    {
        $respuesta = new \stdClass();
        if (!isset($rut) || empty($rut)) {
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El RUT es requerido.');
        }
        if (!isset($roles) || empty($roles)) {
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: Los Roles son requeridos.');
        }
        
        $sso = self::newSSO();
        $AID = config('sso.sso_aid') ?? env('SSO_AID');
        $respuesta->asignar = $sso->AsignarRoles(array('rut' => $rut, 'roles' => $roles, 'AID' => $AID))->AsignarRolesResult;
        
        if (isset($respuesta->asignar->Estado) && $respuesta->asignar->Estado == 1) {
            $respuesta->estado = 'ok';
            return $respuesta;
        }
        
        return self::respuesta(
            $respuesta,
            'error',
            isset($respuesta->asignar->Detalle) ?
                'Advertencia de SSO: ' . $respuesta->asignar->Detalle :
                'Advertencia de SSO.'
        );
    }

    /**
     * Listar roles de usuario
     * 
     * @param string $rut
     * @return object
     */
    public static function listarRolesUsuario($rut)
    {
        try {
            $respuesta = new \stdClass();
            if (isset($rut) && !empty($rut)) {
                $sso = self::newSSO();
                if ($sso->estado_c == self::SSO_CODIGO_ESTADO_OK) {
                    $AID = config('sso.sso_aid') ?? env('SSO_AID');
                    $ROL = config('sso.sso_rol') ?? env('SSO_ROL');
                    $respuesta->usuario = $sso->ListarRolesUsuario(array('rut' => $rut, 'rol' => $ROL, 'AID' => $AID))->ListarRolesUsuarioResult;
                    if (isset($respuesta->usuario->Cantidad) && $respuesta->usuario->Cantidad == 1) {
                        $respuesta->estado = 'ok';
                        return $respuesta;
                    }
                } else {
                    return $sso;
                }
                return self::respuesta($respuesta, 'error', isset($respuesta->usuario->Detalle) ? 'Advertencia de SSO: ' . $respuesta->usuario->Detalle : 'Advertencia de SSO.');
            }
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El RUT es requerido.');
        } catch (\Exception $e) {
            return self::error('Error SSO: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar usuario
     * 
     * @param string $rut
     * @return object
     */
    public static function eliminarUsuario($rut)
    {
        try {
            $respuesta = new \stdClass();
            if (isset($rut) && !empty($rut)) {
                $sso = self::newSSO();
                if ($sso->estado_c == self::SSO_CODIGO_ESTADO_OK) {
                    $AID = config('sso.sso_aid') ?? env('SSO_AID');
                    $respuesta->usuario = $sso->EliminarUsuario(array('rut' => $rut, 'AID' => $AID))->EliminarUsuarioResult;
                    if (isset($respuesta->usuario->Estado) && $respuesta->usuario->Estado == 1) {
                        $respuesta->estado = 'ok';
                        return $respuesta;
                    }
                } else {
                    return $sso;
                }
                return self::respuesta($respuesta, 'error', isset($respuesta->usuario->Detalle) ? 'Advertencia de SSO: ' . $respuesta->usuario->Detalle : 'Advertencia de SSO.');
            }
            return self::respuesta($respuesta, 'error', 'Error Funcion SSO: El RUT es requerido.');
        } catch (\Exception $e) {
            return self::error('Error SSO: ' . $e->getMessage());
        }
    }

    /**
     * Crear respuesta estándar
     * 
     * @param object $respuesta
     * @param string $estado
     * @param string $msg
     * @return object
     */
    public static function respuesta($respuesta, $estado, $msg)
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
    public static function error($msg)
    {
        $sso = new \stdClass();
        $sso->estado = 'error';
        $sso->estado_c = self::SSO_CODIGO_ESTADO_ERROR;
        $sso->estado_msg = $msg;
        return $sso;
    }
} 