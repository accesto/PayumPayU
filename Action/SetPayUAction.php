<?php

namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\Exception\PayUException;
use Accesto\Component\Payum\PayU\Model\Product;
use Accesto\Component\Payum\PayU\OpenPayUWrapper;
use Accesto\Component\Payum\PayU\SetPayU;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Model\Token;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Security\GenericTokenFactory;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;

/**
 * Class SetPayUAction
 * @package Accesto\Component\Payum\PayU\Action
 */
class SetPayUAction implements ApiAwareInterface, ActionInterface, GenericTokenFactoryAwareInterface
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

        $openPayU = $this->getOpenPayUWrapper() ? $this->getOpenPayUWrapper() : new OpenPayUWrapper($environment, $signature, $posId);

        $model = $request->getModel();
        $model = ArrayObject::ensureArrayObject($model);
        /**
         * @var Token $token
         */
        $token = $request->getToken();

        if ($model['orderId'] == null) {
            $order = array();
            $order['continueUrl'] = $token->getTargetUrl(); //customer will be redirected to this page after successfull payment
            $order['notifyUrl'] = $this->tokenFactory->createNotifyToken($token->getGatewayName(),
                $token->getDetails())->getTargetUrl();
            $order['customerIp'] = $model['customerIp'];
            $order['merchantPosId'] = $posId;
            $order['description'] = $model['description'];
            $order['currencyCode'] = $model['currencyCode'];
            $order['totalAmount'] = $model['totalAmount'];
            $order['extOrderId'] = $model['extOrderId']; //must be unique!
            $order['buyer'] = $model['buyer'];
            $order['settings'] = $model['settings'];

            if ($model['payMethods']) {
                $order['payMethods'] = $model['payMethods'];
            }

            if (!array_key_exists('products', $model) || count($model['products']) == 0) {
                $order['products'] = array(
                    array(
                        'name' => $model['description'],
                        'unitPrice' => $model['totalAmount'],
                        'quantity' => 1
                    )
                );
            } else {
                $order['products'] = $model['products'];
            }

            $response = $openPayU->create($order)->getResponse();
            $model['payUResponse'] = $response;

            if ($response && $response->status->statusCode == 'SUCCESS') {
                $model['orderId'] = $response->orderId;
                $request->setModel($model);

                throw new HttpRedirect($response->redirectUri);
            } else {
                throw PayUException::newInstance($response->status);
            }
        } else {
            $response = $openPayU->retrieve($model['orderId'])->getResponse();
            $model['payUResponse'] = $response;

            if ($response->status->statusCode == 'SUCCESS') {
                $model['status'] = $response->orders[0]->status;
                $request->setModel($model);
            }
        }

    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof SetPayU &&
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
}