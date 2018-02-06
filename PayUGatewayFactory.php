<?php

namespace Accesto\Component\Payum\PayU;

use Accesto\Component\Payum\PayU\Action\EncryptTokenAction;
use Accesto\Component\Payum\PayU\Action\NotifyAction;
use Accesto\Component\Payum\PayU\Action\SetPayUAction;
use Accesto\Component\Payum\PayU\Action\StatusAction;
use Accesto\Component\Payum\PayU\Action\CaptureAction;
use Accesto\Component\Payum\PayU\Action\ConvertPaymentAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

/**
 * Class PayUGatewayFactory
 * @package Accesto\Component\Payum\PayU
 */
class PayUGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults(array(
            'payum.factory_name' => 'payu',
            'payum.factory_title' => 'PayU',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.set_payu' => new SetPayUAction(),
            'payum.action.encrypt_card_token' => new EncryptTokenAction(),
            'payum.action.notify' => new NotifyAction(),
        ));

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'environment' => 'secure',
                'pos_id' => '',
                'signature_key' => '',
                'card_token_encryption_key' => null,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = array('environment', 'pos_id', 'signature_key');

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $payuConfig = array(
                    'environment' => $config['environment'],
                    'pos_id' => $config['pos_id'],
                    'signature_key' => $config['signature_key'],
                    'card_token_encryption_key' => $config['card_token_encryption_key'],
                );

                return $payuConfig;
            };
        }
    }
}
