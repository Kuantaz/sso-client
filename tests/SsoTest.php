<?php

namespace Mds\SsoClient\Tests;

use PHPUnit\Framework\TestCase;
use Mds\SsoClient\Sso;

class SsoTest extends TestCase
{
    public function test_sso_constants_are_defined()
    {
        $this->assertEquals(1, Sso::SSO_ROL_ADMIN);
        $this->assertEquals(2, Sso::SSO_ROL_REVISOR);
        $this->assertEquals(3, Sso::SSO_ROL_OPERADOR);
        $this->assertEquals(1, Sso::SSO_CODIGO_ESTADO_OK);
        $this->assertEquals(0, Sso::SSO_CODIGO_ESTADO_ERROR);
    }

    public function test_error_method_returns_correct_structure()
    {
        $error = Sso::error('Test error message');
        
        $this->assertEquals('error', $error->estado);
        $this->assertEquals(Sso::SSO_CODIGO_ESTADO_ERROR, $error->estado_c);
        $this->assertEquals('Test error message', $error->estado_msg);
    }

    public function test_respuesta_method_returns_correct_structure()
    {
        $respuesta = new \stdClass();
        $result = Sso::respuesta($respuesta, 'ok', 'Test message');
        
        $this->assertEquals('ok', $result->estado);
        $this->assertEquals('Test message', $result->estado_msg);
    }
} 