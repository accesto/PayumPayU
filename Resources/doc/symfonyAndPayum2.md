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
            'invoiceDisabled' => true, // use this only when you want to hide invoice checkbox on payment form
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

```yaml
# Acme\PaymentBundle\Resources\config\services.yml

acme_payment.payum.extension.event_dispatcher:
      class: Payum\Core\Bridge\Symfony\Extension\EventDispatcherExtension
      arguments: ["@event_dispatcher"]
      tags:
          - { name: payum.extension, all: true, prepend: false }

acme_payment.listener.update_payment_status:
        class: Acme\PaymentBundle\Event\Listener\UpdatePaymentStatus
        tags:
          - { name: kernel.event_listener, event: payum.gateway.post_execute, method: updateStatus }

acme_payment.payu_gateway_factory:
        class: Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder
        arguments: [Accesto\Component\Payum\PayU\PayUGatewayFactory]
        tags:
            - { name: payum.gateway_factory_builder, factory: payu }

```

### Recurring payments

[PayU documentation](http://developers.payu.com/pl/recurring.html#recurring_description)

There is a special widget which allows to obtain tokenized credit card data (tokenization must be switch on by PayU)

#### Model for stroing tokens

You can store token as plain text (e.g. in database), in such case create new Entity which implements Payum\Core\Model\CreditCardInterface

or such token can be encrypted/decrypted, then implements Accesto\Component\Payum\PayU\CreditCardEncrypted

--- This part only for encrypted tokens ---

Add additional parameter to config.yml under payu gateway config:
```yml
    card_token_encryption_key: 'some random string'
```

Next in Controller action which handles payu widget form use such code:

```php
        $encryptToken = new EncryptToken($request->request->all()); // you can also use only parameter 'value' as ['value' => '...'] provided by payu,
                                                                    // instead $request->request->all()
        $this->get('payum')->getGateway('payu')->execute($encryptToken);
        $model = $encryptToken->getModel();
```

returned $model looks like this

```php
         [
            'token' => 'encrypted and encoded token',
            'salt' => 'encoded salt',
         ]
```

Store token and salt in your entity.

--- Non encrypted tokens ---

Just store in your entity token provided by payu, it will be send to action which handles payu widget form as $_POST variable called 'value'.

```php
    $token = $request->request->get('value');
    ... // store token in entity
```

--- This steps are the same for encrypted and not encrypted tokens ---

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

        $cardDetails = ... ;// This is an object which contains token and implements CreditCardInterface (for non encrypted)
                            // or encryptedToken and salt and implements CreditCardEncrypted for encrypted tokens

        $payment->setCreditCard($cardDetails);
        $details['recurring'] = true;
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