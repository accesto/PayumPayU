<?php

namespace Accesto\Component\Payum\PayU;

/**
 * Class OpenPayUWrapper
 * @package Accesto\Component\Payum\PayU
 */
class OpenPayUWrapper
{
    public function __construct($environment, $signatureKey, $posId)
    {
        \OpenPayU_Configuration::setEnvironment($environment);
        \OpenPayU_Configuration::setMerchantPosId($posId);
        \OpenPayU_Configuration::setSignatureKey($signatureKey);
    }

    public function create($order)
    {
        return \OpenPayU_Order::create($order);
    }

    public function retrieve($id)
    {
        return \OpenPayU_Order::retrieve($id);
    }
}
