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
use Payum\Core\Model\PaymentInterface;
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

        $openPayU = $this->getOpenPayUWrapper() ? $this->getOpenPayUWrapper() : new OpenPayUWrapper(
            $environment,
            $signature,
            $posId,
            $this->api['oauth_client_id'],
            $this->api['oauth_secret']
        );

        $model = $request->getModel();
        $firstModel = $request->getFirstModel();
        $model = ArrayObject::ensureArrayObject($model);

        /**
         * @var Token $token
         */
        $token = $request->getToken();
        if ($model['orderId'] == null) {
            $order = array();
            $order = $this->setUrls($token, $order);
            $order = $this->setBaseData($model, $order, $posId);
            $order = $this->setProducts($model, $order);

            if (isset($model['recurring']) && $model['recurring'] == OpenPayUWrapper::RECURRING_STANDARD) {
                $order = $this->setRecurringPayment($openPayU, $model, $order);
            }

            try {
                $response = $openPayU->create($order)->getResponse();
                $model['payUResponse'] = $response;
            } catch (\OpenPayU_Exception_Request $exception) {
                throw PayUException::newInstance(null, $firstModel);
            }

            if ($response && $response->status->statusCode == 'SUCCESS') {
                $this->updateModel($model, $response, $firstModel);
                $request->setModel($model);

                throw new HttpRedirect(isset($response->redirectUri) ? $response->redirectUri : $token->getTargetUrl());
            } elseif ($response && $response->status->statusCode == 'WARNING_CONTINUE_3DS') {
                $this->updateModel($model, $response, $firstModel);
                $request->setModel($model);

                throw new HttpRedirect($response->redirectUri);
            } else {
                throw PayUException::newInstance($response->status, $firstModel);
            }
        } else {
            $this->updateStatus($request, $openPayU, $model);
        }
    }

    private function updateModel(&$model, $response, $firstModel = null)
    {
        $model['orderId'] = $response->orderId;
        if (isset($response->payMethods) && isset($response->payMethods->payMethod)) {
            $model['creditCardMaskedNumber'] = $response->payMethods->payMethod->card->number;
            if ($firstModel instanceof PaymentInterface && $firstModel->getCreditCard()) {
                $firstModel->getCreditCard()->setMaskedNumber($response->payMethods->payMethod->card->number);
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

    private function findPrefferedToken(array $tokens, $creditCardMaskedNumber = null)
    {
        if (!count($tokens)) {
            return;
        }
        $tokens = array_filter($tokens, function ($token) use ($creditCardMaskedNumber) {
            return $token->status == 'ACTIVE' && (null == $creditCardMaskedNumber || $token->cardNumberMasked == $creditCardMaskedNumber);
        });
        if (!count($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            if ($token->preferred) {
                return $token;
            }
        }

        return reset($tokens);
    }

    /**
     * @param $model
     * @param $order
     *
     * @return mixed
     */
    private function setProducts($model, $order)
    {
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

        return $order;
    }

    /**
     * @param $token
     * @param $order
     *
     * @return mixed
     */
    private function setUrls($token, $order)
    {
        $order['continueUrl'] = $token->getTargetUrl(); //customer will be redirected to this page after successfull payment
        $order['notifyUrl'] = $this->tokenFactory->createNotifyToken($token->getGatewayName(),
            $token->getDetails())->getTargetUrl();

        return $order;
    }

    /**
     * @param $model
     * @param $order
     * @param $posId
     *
     * @return mixed
     */
    private function setBaseData($model, $order, $posId)
    {
        $order['customerIp'] = $model['customerIp'];
        $order['merchantPosId'] = $posId;
        $order['description'] = $model['description'];
        $order['currencyCode'] = $model['currencyCode'];
        $order['totalAmount'] = $model['totalAmount'];
        $order['extOrderId'] = $model['extOrderId']; //must be unique!
        $order['buyer'] = $model['buyer'];
        if (isset($model['payMethods'])) {
            $order['payMethods'] = $model['payMethods'];
        }

        if (isset($model['validityTime']) && is_numeric($model['validityTime'])) {
            $order['validityTime'] = (int)$model['validityTime'];
        }
        if (isset($model['invoiceDisabled'])) {
            $order['settings'] = [
                'invoiceDisabled' => $model['invoiceDisabled'],
            ];
        }
        if (isset($model['language'])) {
            $order['buyer']['language'] = $model['language'];
        }

        return $order;
    }

    /**
     * @param $openPayU
     * @param $model
     * @param $order
     *
     * @return array
     */
    private function setRecurringPayment($openPayU, $model, $order)
    {
        $payMethods = $openPayU->retrievePayMethods($model['client_id'], $model['client_email'])->getResponse();
        if (!isset($payMethods->cardTokens)) {
            throw new \InvalidArgumentException('Cannot make this recurring payment. Token for user does not exist');
        }
        $cardToken = $this->findPrefferedToken($payMethods->cardTokens,
            isset($model['creditCardMaskedNumber']) ? $model['creditCardMaskedNumber'] : null);
        if (!$cardToken) {
            throw new \InvalidArgumentException('Cannot make this recurring payment. Token for user does not exist');
        }
        $order['recurring'] = $model['recurring'];

        $order['payMethods'] = [
            'payMethod' => [
                'value' => $cardToken->value,
                'type' => 'CARD_TOKEN',
            ],
        ];

        return $order;
    }

    /**
     * @param $request
     * @param $openPayU
     * @param $model
     */
    private function updateStatus($request, $openPayU, $model)
    {
        $response = $openPayU->retrieve($model['orderId'])->getResponse();
        if ($response->status->statusCode == 'SUCCESS') {
            $model['status'] = $response->orders[0]->status;

            $request->setModel($model);
        }
    }
}
