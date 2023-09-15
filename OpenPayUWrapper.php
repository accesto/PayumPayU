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

    protected $oauthClientId;

    protected $oauthSecret;

    public function __construct($environment, $signatureKey, $posId, $oauthClientId = null, $oauthSecret = null)
    {
        \OpenPayU_Configuration::setEnvironment($environment);
        \OpenPayU_Configuration::setMerchantPosId($posId);
        \OpenPayU_Configuration::setSignatureKey($signatureKey);
        $this->oauthClientId = $oauthClientId;
        $this->oauthSecret = $oauthSecret;
    }

    public function create($order)
    {
        return \OpenPayU_Order::create($order);
    }

    public function refund($id, $description = 'Refund')
    {
        return \OpenPayU_Refund::create($id, $description);
    }

    public function retrieve($id)
    {
        return \OpenPayU_Order::retrieve($id);
    }

    public function retrievePayMethods($userId, $userEmail)
    {
        \OpenPayU_Configuration::setOauthClientId($this->oauthClientId);
        \OpenPayU_Configuration::setOauthClientSecret($this->oauthSecret);
        \OpenPayU_Configuration::setOauthGrantType(\OauthGrantType::TRUSTED_MERCHANT);
        \OpenPayU_Configuration::setOauthEmail($userEmail);
        \OpenPayU_Configuration::setOauthExtCustomerId($userId);

        return \OpenPayU_Retrieve::payMethods();
    }
}
