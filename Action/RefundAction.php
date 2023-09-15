<?php

namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\RefundPayU;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\Refund;

/**
 * Class RefundAction.
 */
class RefundAction extends GatewayAwareAction implements ActionInterface
{
    /**
     * @param mixed $request
     *
     * @throws \Payum\Core\Exception\RequestNotSupportedException if the action dose not support the request.
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $this->gateway->execute($convert = new Convert($request->getFirstModel(), 'array', $request->getToken()));

        $refundPayU = new RefundPayU($request->getToken());
        $refundPayU->setModel($request->getFirstModel());
        $refundPayU->setModel($request->getModel());
        $this->gateway->execute($refundPayU);
    }

    /**
     * @param mixed $request
     *
     * @return boolean
     */
    public function supports($request)
    {
        return
            $request instanceof Refund &&
            $request->getFirstModel() instanceof PaymentInterface;
    }
}
