<?php

namespace Accesto\Component\Payum\PayU;

/**
 * Class OpenPayUWrapper
 * @package Accesto\Component\Payum\PayU
 */
class OpenPayUWrapper
{
    const RECURRING_FIRST = 'FIRST';
    const RECURRING_STANDARD = 'STANDARD';

    public function __construct($environment, $signatureKey, $posId)
    {
        \OpenPayU_Configuration::setEnvironment($environment);
        \OpenPayU_Configuration::setMerchantPosId($posId);
        \OpenPayU_Configuration::setSignatureKey($signatureKey);
        \OpenPayU_Configuration::setOauthClientId('313851');
        \OpenPayU_Configuration::setOauthClientSecret('709be1e5d787d8d393c886656b5d79f6');
        \OpenPayU_Configuration::setOauthGrantType(\OauthGrantType::TRUSTED_MERCHANT);
    }

    public function create($order)
    {
        return \OpenPayU_Order::create($order);
    }

    public function retrieve($id)
    {
        return \OpenPayU_Order::retrieve($id);
    }

    public function retrievePayMethods($userId, $userEmail)
    {
        \OpenPayU_Configuration::setOauthEmail($userEmail);
        \OpenPayU_Configuration::setOauthExtCustomerId($userId);

        return \OpenPayU_Retrieve::payMethods();
    }
}
