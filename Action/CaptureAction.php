<?php

namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\SetPayU;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Model\Token;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Model\Identity;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactory;

/**
 * Class CaptureAction
 * @package Accesto\Component\Payum\PayU\Action
 */
class CaptureAction extends GatewayAwareAction implements ActionInterface
{
    /**
     * @param mixed $request
     *
     * @throws \Payum\Core\Exception\RequestNotSupportedException if the action dose not support the request.
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());

        $setPayU = new SetPayU($request->getToken());
        $setPayU->setModel($details);
        $this->gateway->execute($setPayU);
    }

    /**
     * @param mixed $request
     *
     * @return boolean
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
