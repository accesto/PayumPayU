<?php


namespace Accesto\Component\Payum\PayU;

use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Gateway\AbstractGatewayFactory;
use Payum\Core\Action\CapturePaymentAction;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PayUPaymentFactory
 * @package Accesto\Component\Payum\PayU
 */
class PayUPaymentFactory extends AbstractGatewayFactory
{
    /**
     * @param ArrayNodeDefinition $builder
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        parent::addConfiguration($builder);

        $builder
            ->children()
            ->scalarNode('environment')->isRequired()->defaultValue('secure')->end()
            ->scalarNode('pos_id')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('signature_key')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('card_token_encryption_key')->defaultNull()->end()
            ->end()
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPayumGatewayFactoryClass()
    {
        return 'Accesto\Component\Payum\PayU\PayUGatewayFactory';
    }

    /**
     * The payment name,
     * For example paypal_express_checkout_nvp or authorize_net_aim
     *
     * @return string
     */
    public function getName()
    {
        return 'payu';
    }
}
