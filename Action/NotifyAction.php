<?php

namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\RefundPayU;
use Accesto\Component\Payum\PayU\SetPayU;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Notify;
use Payum\Core\Request\Sync;
use Payum\Core\Reply\HttpResponse;

/**
 * Class NotifyAction
 * @package Accesto\Component\Payum\PayU\Action
 */
class NotifyAction extends GatewayAwareAction implements ActionInterface
{

    /**
     * @param Notify $request
     *
     * @throws \Payum\Core\Exception\RequestNotSupportedException if the action dose not support the request.
     */
    public function execute($request)
    {
        /** @var $request Notify */
        RequestNotSupportedException::assertSupports($this, $request);
        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        $content = json_decode($getHttpRequest->content, true);
        if ($content && isset($content['refund'])) {
            $refundPayU = new RefundPayU($request->getToken());
            $refundPayU->setModel($request->getFirstModel());
            $model = $request->getModel();
            $model['refund'] = $content['refund'];
            $refundPayU->setModel($model);

            $this->gateway->execute($refundPayU);
            $request->setModel($refundPayU->getModel());
        } else {
            $setPayU = new SetPayU($request->getToken());
            $setPayU->setModel($request->getModel());

            $this->gateway->execute($setPayU);
        }

        $status = new GetHumanStatus($request->getToken());
        $status->setModel($request->getFirstModel());
        $status->setModel($request->getModel());
        $this->gateway->execute($status);
    
        throw new HttpResponse('OK', 200);
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
