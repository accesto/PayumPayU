<?php
namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\Model\Product;
use Accesto\Component\Payum\PayU\OpenPayUWrapper;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
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
        $details['customerIp'] = isset($details['customerIp']) ? $details['customerIp'] : (array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : null);
        $details['creditCardMaskedNumber'] = $order->getCreditCard() ? $order->getCreditCard()->getMaskedNumber() : null;
        $d = $order->getDetails();
        if (isset($d['recurring']) && $d['recurring']) {
            if ($d['recurring'] != OpenPayUWrapper::RECURRING_FIRST) {
                $details['recurring'] = $d['recurring'];
            } elseif ($order->getCreditCard() && $order->getCreditCard()->getToken()) {
                $details['payMethods'] = [
                    'payMethod' => [
                        'value' => $order->getCreditCard()->getToken(),
                        'type' => 'CARD_TOKEN',
                    ],
                ];
            }
        }
        $details['buyer'] = array(
            'email' => $order->getClientEmail(),
            'firstName' => isset($d['firstName']) ? $d['firstName'] : '',
            'lastName' => isset($d['lastName']) ? $d['lastName'] : '',
            'extCustomerId' => $details['client_id'],
        );
        $details['status']  = 'NEW';

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
