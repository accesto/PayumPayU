<?php

namespace Accesto\Component\Payum\PayU\Tests\Action;

use Accesto\Component\Payum\PayU\Action\StatusAction;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Tests\Action\GatewayAwareActionTest;

/**
 * Class StatusActionTest
 * @package Accesto\Component\Payum\PayU\Tests\Action
 */
class StatusActionTest extends GatewayAwareActionTest
{
    protected $requestClass = 'Payum\Core\Request\GetHumanStatus';
    protected $actionClass = 'Accesto\Component\Payum\PayU\Action\StatusAction';

    /**
     * @test
     */
    public function shouldBeSubClassOfGatewayAwareAction()
    {
        $rc = new \ReflectionClass(StatusAction::class);
        $this->assertTrue($rc->implementsInterface('Payum\Core\Action\ActionInterface'));
    }

    /**
     * @test
     */
    public function shouldMarkAsNew()
    {
        $request = new GetHumanStatus(array(
            'status' => 'NEW'
        ));

        $action = new StatusAction();
        $action->execute($request);

        $this->assertTrue($request->isNew(), 'Request should be marked as new');
    }

    /**
     * @test
     */
    public function shouldMarkAsPending()
    {
        $request = new GetHumanStatus(array(
            'status' => 'PENDING'
        ));

        $action = new StatusAction();
        $action->execute($request);

        $this->assertTrue($request->isPending(), 'Request should be marked as pending');
    }

    /**
     * @test
     */
    public function shouldMarkAsCaptured()
    {
        $request = new GetHumanStatus(array(
            'status' => 'COMPLETED'
        ));

        $action = new StatusAction();
        $action->execute($request);

        $this->assertTrue($request->isCaptured(), 'Request should be marked as captured');
    }
}
