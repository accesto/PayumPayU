<?php
namespace Accesto\Component\Payum\PayU\Action;

use Accesto\Component\Payum\PayU\CardTokenEncryptor;
use Accesto\Component\Payum\PayU\CreditCardEncrypted;
use Accesto\Component\Payum\PayU\Model\Product;
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
class ConvertPaymentAction extends GatewayAwareAction implements ApiAwareInterface
{
    private $api = array();

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
        if (isset($d['recurring']) && $d['recurring']) {
            if (!$order->getCreditCard()) {
                throw new \InvalidArgumentException('Credit card token required for recurring transaction');
            }
            $details['recurring'] = 'STANDARD';
            $creditCard = $order->getCreditCard();
            if ($creditCard instanceof CreditCardEncrypted) {
                if (!$order->getCreditCard()->getEncryptedToken()) {
                    throw new \InvalidArgumentException('Credit card encrypted token required for recurring transaction');
                }
                $token = CardTokenEncryptor::decrypt(
                    $creditCard->getEncryptedToken(),
                    $this->api['card_token_encryption_key'],
                    $creditCard->getSalt()
                );
            } else {
                if (!$creditCard->getToken()) {
                    throw new \InvalidArgumentException('Credit card token required for recurring transaction');
                }
                $token = $creditCard->getToken();
            }
            $details['payMethods'] = [
                'payMethod' => [
                    'value' => $token,
                    'type' => 'CARD_TOKEN',
                ],
            ];
        }
        $details['buyer'] = array(
            'email' => $order->getClientEmail(),
            'firstName' => isset($d['firstName']) ? $d['firstName'] : '',
            'lastName' => isset($d['lastName']) ? $d['lastName'] : '',
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
