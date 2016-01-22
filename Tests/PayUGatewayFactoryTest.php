<?php

namespace Accesto\Component\Payum\PayU\Tests;

use Accesto\Component\Payum\PayU\PayUGatewayFactory;

/**
 * Class PayUGatewayFactoryTest
 * @package Accesto\Component\Payum\PayU\Tests
 */
class PayUGatewayFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldSubClassGatewayFactory()
    {
        $rc = new \ReflectionClass('Accesto\Component\Payum\PayU\PayUGatewayFactory');
        $this->assertTrue($rc->isSubclassOf('Payum\Core\GatewayFactory'));
    }
    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new PayUGatewayFactory();
    }

    /**
     * @test
     */
    public function shouldCreateCoreGatewayFactoryIfNotPassed()
    {
        $factory = new PayUGatewayFactory();
        $this->assertAttributeInstanceOf('Payum\Core\CoreGatewayFactory', 'coreGatewayFactory', $factory);
    }

    /**
     * @test
     */
    public function shouldAllowCreateGateway()
    {
        $factory = new PayUGatewayFactory();
        $gateway = $factory->create(array(
            'pos_id' => 'aName',
            'signature_key' => 'aPass',
            'environment' => 'secure',
        ));
        $this->assertInstanceOf('Payum\Core\Gateway', $gateway);
        $this->assertAttributeNotEmpty('apis', $gateway);
        $this->assertAttributeNotEmpty('actions', $gateway);
        $extensions = $this->readAttribute($gateway, 'extensions');
        $this->assertAttributeNotEmpty('extensions', $extensions);
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayConfig()
    {
        $factory = new PayUGatewayFactory();
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertNotEmpty($config);
    }

    /**
     * @test
     */
    public function shouldAddDefaultConfigPassedInConstructorWhileCreatingGatewayConfig()
    {
        $factory = new PayUGatewayFactory(array(
            'pos_id' => 'fooVal',
            'signature_key' => 'barVal',
        ));
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('pos_id', $config);
        $this->assertEquals('fooVal', $config['pos_id']);
        $this->assertArrayHasKey('signature_key', $config);
        $this->assertEquals('barVal', $config['signature_key']);
    }

    /**
     * @test
     */
    public function shouldConfigContainDefaultOptions()
    {
        $factory = new PayUGatewayFactory();
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('payum.default_options', $config);
        $this->assertEquals(
            array('environment' => 'secure', 'pos_id' => '', 'signature_key' => ''),
            $config['payum.default_options']
        );
    }

    /**
     * @test
     */
    public function shouldConfigContainFactoryNameAndTitle()
    {
        $factory = new PayUGatewayFactory();
        $config = $factory->createConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('payum.factory_name', $config);
        $this->assertEquals('payu', $config['payum.factory_name']);
        $this->assertArrayHasKey('payum.factory_title', $config);
        $this->assertEquals('PayU', $config['payum.factory_title']);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The pos_id, signature_key fields are required.
     */
    public function shouldThrowIfRequiredOptionsNotPassed()
    {
        $factory = new PayUGatewayFactory();
        $factory->create();
    }
}
