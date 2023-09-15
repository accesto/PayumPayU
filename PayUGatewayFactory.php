<?php

namespace Accesto\Component\Payum\PayU;

use Accesto\Component\Payum\PayU\Action\NotifyAction;
use Accesto\Component\Payum\PayU\Action\RefundAction;
use Accesto\Component\Payum\PayU\Action\RefundPayUAction;
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
            'payum.action.notify' => new NotifyAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.set_payu' => new SetPayUAction(),
            'payum.action.refund_payu' => new RefundPayUAction(),
        ));

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'environment' => 'secure',
                'pos_id' => '',
                'signature_key' => '',
                'oauth_client_id' => '',
                'oauth_secret' => '',
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = array('environment', 'pos_id', 'signature_key', 'oauth_client_id', 'oauth_secret');

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $payuConfig = array(
                    'environment' => $config['environment'],
                    'pos_id' => $config['pos_id'],
                    'signature_key' => $config['signature_key'],
                    'oauth_client_id' => $config['oauth_client_id'],
                    'oauth_secret' => $config['oauth_secret'],
                );

                return $payuConfig;
            };
        }
    }
}
