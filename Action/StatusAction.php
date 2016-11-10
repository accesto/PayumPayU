<?php

namespace Accesto\Component\Payum\PayU\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Offline\Constants;

/**
 * Class StatusAction.
 */
class StatusAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /* @var $request GetStatusInterface */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (null === $model['status'] || 'NEW' == $model['status']) {
            $request->markNew();

            return;
        } elseif ($model['status'] == 'PENDING') {
            $request->markPending();

            return;
        } elseif ($model['status'] == 'COMPLETED') {
            $request->markCaptured();

            return;
        } elseif ($model['status'] == 'CANCELED') {
            $request->markCanceled();

            return;
        } elseif ($model['status'] == 'REJECTED') {
            $request->markFailed();

            return;
        }

        $request->markUnknown();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }
}
