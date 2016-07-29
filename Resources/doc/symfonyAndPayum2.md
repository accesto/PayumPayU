## Instalation
add to your composer.json
```json
{
    "require": {
        "accesto/payum-pay-u": "dev-master",
        "payum/payum-bundle": "^2.0"
    }
}
```

Add proper configuration

```yaml
### app\config.yml

payum:
    security:
        token_storage:
            Acme\PaymentBundle\Entity\PaymentToken: { doctrine: orm }
    storages:
        Acme\PaymentBundle\Entity\Payment: { doctrine: orm }
    gateways:
        ...
        payu:
            factory: payu
            environment: secure
            pos_id: 145227
            signature_key: 13a980d4f851f3d9a1cfc792fb1f5e50
        ...
```

## Usage

### Prepare payment

```php
    public function prepareAction(Request $request)
    {
        $order = ...;
        $paymentName = 'payu';

        $storage = $this->get('payum')
            ->getStorage('Acme\PaymentBundle\Entity\Payment');

        $payment = $storage->create();        
        
        $payment->setCurrencyCode('PLN');
        $payment->setTotalAmount($order->getPrice() * 100);
        $payment->setDescription($order->getDescription());
        $payment->setClientId(md5($order->getClientId()));
        $payment->setClientEmail($order->getClientEmail());

        $details = array(
            'firstName' => $order->getClientFirstName(),
            'lastName' => $order->getClientLastName(),
        );
        
        $payment->setDetails($details);

        $storage->update($payment);

        $captureToken = $this->get('payum')->getTokenFactory()
            ->createCaptureToken(
                $paymentName,
                $payment,
                'acme_payment_done' // the route to redirect after capture;
            );

        return $this->redirect($captureToken->getTargetUrl());
    }
```

### Handling gateway response

```php
    public function doneAction(Request $request)
    {
        $token = $this->get('payum')->getHttpRequestVerifier()->verify($request);
        $payment = $this->get('payum')->getGateway($token->getGatewayName());

        $payment->execute($status = new GetHumanStatus($token));
        $paymentId = $status->getToken()->getDetails()->getId();
        $paymentClass = $status->getToken()->getDetails()->getClass();
        $storage = $this->get('payum')->getStorage($paymentClass);

        $payment = $storage->find($paymentId);
        if (!$payment) {
            throw new HttpResponse('Payment not found');
        }

        if ($status->isCaptured()) {
            // payment succeeded
        } elseif ($status->isPending()) {
            // payment pending
        } else {
            // payment failed
        }

        return new RedirectResponse('/');
    }
```

### Handling payment status updates
PayU usually confirm payment after few minutes. The easiest way to check payment status updates is to create proper listener.

```php
<?php

namespace Acme\PaymentBundle\Event\Listener\UpdatePaymentStatus

use Payum\Core\Bridge\Symfony\Event\ExecuteEvent;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetStatusInterface;
use Acme\PaymentBundle\Entity\Payment;

class UpdatePaymentStatus
{
    public function updateStatus(ExecuteEvent $event)
    {
        $request = $event->getContext()->getRequest();

        if ($request instanceof GetStatusInterface && $request instanceof Generic) {
            $payment = $request->getFirstModel();
            if ($request->isCaptured() && $payment instanceof Payment) {
                // payment is completed and succeeded
                // do whatever you want
            }
        }
    }
}
```