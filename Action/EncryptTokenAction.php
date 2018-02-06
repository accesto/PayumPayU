<?php

namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\CardTokenEncryptor;
use Accesto\Component\Payum\PayU\EncryptToken;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Bridge\Spl\ArrayObject;

/**
 * Class EncryptTokenAction.
 */
class EncryptTokenAction implements ApiAwareInterface, ActionInterface
{
    protected $api = [];

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

        if (!isset($api['card_token_encryption_key']) || !$api['card_token_encryption_key']) {
            throw new \InvalidArgumentException('card_token_encryption_key must be set');
        }

        $this->api = $api;
    }

    /**
     * @param EncryptToken $request
     *
     * @throws \Payum\Core\Exception\RequestNotSupportedException if the action dose not support the request.
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getFirstModel());
        list($token, $salt) = CardTokenEncryptor::encrypt(
            $details['value'],
            $this->api['card_token_encryption_key']
        );

        $request->setModel(['token' => $token, 'salt' => $salt]);
    }

    /**
     * @param mixed $request
     *
     * @return boolean
     */
    public function supports($request)
    {
        return
            $request instanceof EncryptToken &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
