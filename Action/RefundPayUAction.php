<?php

namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\Exception\PayUException;
use Accesto\Component\Payum\PayU\Model\Product;
use Accesto\Component\Payum\PayU\OpenPayUWrapper;
use Accesto\Component\Payum\PayU\RefundPayU;
use Accesto\Component\Payum\PayU\SetPayU;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Model\Token;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Security\GenericTokenFactory;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;

/**
 * Class RefundPayUAction
 * @package Accesto\Component\Payum\PayU\Action
 */
class RefundPayUAction implements ApiAwareInterface, ActionInterface, GenericTokenFactoryAwareInterface
{
    /**
     * @var GenericTokenFactory
     */
    protected $tokenFactory;

    protected $api = array();

    /**
     * @var OpenPayUWrapper
     */
    protected $openPayUWrapper;

    /**
     * @param GenericTokenFactoryInterface $genericTokenFactory
     *
     * @return void
     */
    public function setGenericTokenFactory(GenericTokenFactoryInterface $genericTokenFactory = null)
    {
        $this->tokenFactory = $genericTokenFactory;
    }

    /**
     * @param mixed $api
     *
     * @throws UnsupportedApiException if the given Api is not supported.
     */
    public function setApi($api)
    {
        if (!is_array($api)) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $environment = $this->api['environment'];
        $signature = $this->api['signature_key'];
        $posId = $this->api['pos_id'];

        /** @var PaymentInterface $firstModel */
        $firstModel = $request->getFirstModel();
        $details = $request->getModel();
        ArrayObject::ensureArrayObject($details);

        $model = $request->getModel();
        if (isset($model['refund']) && isset($model['refund']['refundId'])) {
            $this->updateRefundStatus($details, $model['refund']['status'], $request);

            return;
        }

        $openPayU = $this->getOpenPayUWrapper() ? $this->getOpenPayUWrapper() : new OpenPayUWrapper(
            $environment,
            $signature,
            $posId,
            $this->api['oauth_client_id'],
            $this->api['oauth_secret']
        );



        if (!isset($details['orderId']) || !($orderId = $details['orderId'])) {
            throw PayUException::newInstance(null, $firstModel);
        }

        try {
            $response = $openPayU->refund($orderId)->getResponse();
            $this->updateRefundStatus($details, $response->refund->status, $request);
        } catch (\Exception $exception) {
            throw PayUException::newInstance(null, $firstModel);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof RefundPayU &&
            $request->getModel() instanceof \ArrayObject;
    }

    /**
     * @return OpenPayUWrapper
     */
    public function getOpenPayUWrapper()
    {
        return $this->openPayUWrapper;
    }

    /**
     * @param OpenPayUWrapper $openPayUWrapper
     */
    public function setOpenPayUWrapper($openPayUWrapper)
    {
        $this->openPayUWrapper = $openPayUWrapper;
    }

    private function updateRefundStatus($model, $status, $request)
    {
        $model['status'] = 'REFUND_'.$status;

        $request->setModel($model);
    }
}
