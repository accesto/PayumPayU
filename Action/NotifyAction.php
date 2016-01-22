<?php

namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\SetPayU;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Notify;
use Payum\Core\Request\Sync;

/**
 * Class NotifyAction
 * @package Accesto\Component\Payum\PayU\Action
 */
class NotifyAction extends GatewayAwareAction implements ActionInterface
{

    /**
     * @param mixed $request
     *
     * @throws \Payum\Core\Exception\RequestNotSupportedException if the action dose not support the request.
     */
    public function execute($request)
    {
        /** @var $request Notify */
        RequestNotSupportedException::assertSupports($this, $request);
        $setPayU = new SetPayU($request->getToken());
        $setPayU->setModel($request->getModel());;
        $this->gateway->execute($setPayU);
        $status = new GetHumanStatus($request->getToken());
        $status->setModel($request->getModel());
        $this->gateway->execute($status);
    }


    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayObject
            ;
    }
}
