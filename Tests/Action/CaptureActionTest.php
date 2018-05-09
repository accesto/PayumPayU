<?php

namespace Accesto\Component\Payum\PayU\Tests\Action;

use Accesto\Component\Payum\PayU\Action\CaptureAction;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Tests\GenericActionTest;

/**
 * Class CaptureActionTest
 * @package Accesto\Component\Payum\PayU\Tests\Action
 */
class CaptureActionTest extends GenericActionTest
{
    protected $requestClass = Capture::class;
    protected $actionClass = CaptureAction::class;

    /**
     * @test
     */
    public function shouldBeSubClassOfGatewayAwareAction()
    {
        $rc = new \ReflectionClass(CaptureAction::class);
        $this->assertTrue($rc->isSubclassOf(GatewayAwareAction::class));
    }

    /**
     * @test
     */
    public function shouldExecuteSetPayU()
    {
        $action = new CaptureAction();
        $gateway = $this->createGatewayMock();
        $gateway->expects($this->once())->method('execute')
            ->with($this->isInstanceOf('Accesto\Component\Payum\PayU\SetPayU'));

        $action->setGateway($gateway);

        $action->execute(new Capture([]));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Payum\Core\GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->getMock(GatewayInterface::class);
    }
}
