<?php
namespace Accesto\Component\Payum\PayU\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Offline\Constants;

/**
 * Class StatusAction
 * @package Accesto\Component\Payum\PayU\Action
 */
class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request GetStatusInterface */
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
        }

        $request->markUnknown();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }
}
