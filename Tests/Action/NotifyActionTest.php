<?php

namespace Accesto\Component\Payum\PayU\Tests\Action;

use Accesto\Component\Payum\PayU\Action\NotifyAction;
use Payum\Core\Request\Notify;
use Payum\Core\Tests\GenericActionTest;

/**
 * Class NotifyActionTest
 * @package Accesto\Component\Payum\PayU\Tests\Action
 */
class NotifyActionTest extends GenericActionTest
{
    protected $requestClass = 'Payum\Core\Request\Notify';
    protected $actionClass = 'Accesto\Component\Payum\PayU\Action\NotifyAction';

    /**
     * @test
     */
    public function shouldBeSubClassOfGatewayAwareAction()
    {
        $rc = new \ReflectionClass('Accesto\Component\Payum\PayU\Action\NotifyAction');
        $this->assertTrue($rc->isSubclassOf('Payum\Core\Action\GatewayAwareAction'));
    }

    /**
     * @test
     */
    public function shouldExecuteGetHumanStatusAndSetPayU()
    {
        $action = new NotifyAction();
        $gateway = $this->createGatewayMock();

        $gateway->expects($this->exactly(2))->method('execute')
            ->with(
                $this->logicalOr (
                    $this->isInstanceOf('Accesto\Component\Payum\PayU\SetPayU'),
                    $this->isInstanceOf('Payum\Core\Request\GetHumanStatus')
                )
            );

        $action->setGateway($gateway);

        $action->execute(new Notify([]));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Payum\Core\GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->getMock('Payum\Core\GatewayInterface');
    }
}
