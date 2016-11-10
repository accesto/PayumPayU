<?php
namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\Model\Product;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

/**
 * Class ConvertPaymentAction
 * @package Accesto\Component\Payum\PayU\Action
 */
class ConvertPaymentAction extends GatewayAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var $order PaymentInterface
         */
        $order = $request->getSource();
        $details = ArrayObject::ensureArrayObject($order->getDetails());
        $details['totalAmount'] = $order->getTotalAmount();
        $details['currencyCode'] = $order->getCurrencyCode();
        $details['extOrderId'] = $order->getNumber();
        $details['description'] = $order->getDescription();
        $details['client_email'] = $order->getClientEmail();
        $details['client_id'] = $order->getClientId();
        $details['customerIp'] = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : null;
        $d = $order->getDetails();
        $details['buyer'] = array(
            'email' => $order->getClientEmail(),
            'firstName' => isset($d['firstName']) ? $d['firstName'] : '',
            'lastName' => isset($d['lastName']) ? $d['lastName'] : '',
            'language' => $order->getLocale() ? substr($order->getLocale(), 0, 2) : '',
        );
        $details['status']  = 'NEW';

        $details['settings'] = array(
            'invoiceDisabled' => true
        );

        if ($order->getPaymentForm() == 'card') {
            $details['payMethods'] = array(
                'payMethod' => ['type' => 'PBL', 'value' => 'c']
            );
        }

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
            ;
    }
}
