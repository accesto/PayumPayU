## Instalation
add to your composer.json
```json
{
    "require": {
        "accesto/payum-pay-u": "dev-master",
        "payum/payum-bundle": "1.0.x-dev"
    }
}
```

Add PayUPaymentFactory to payum:

```php
<?php

// Acme/PaymentBundle/AcmePaymentBundle.php

namespace Acme\PaymentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Accesto\Component\Payum\PayU\PayUPaymentFactory;

class AcmePaymentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $payumExtension = $container->getExtension('payum');
        $payumExtension->addGatewayFactory(new PayUPaymentFactory());
    }
}
```

Add proper configuration

```yaml
# app\config.yml

payum:
    security:
        token_storage:
            Acme\PaymentBundle\Entity\PaymentToken: { doctrine: orm }
    storages:
        Acme\PaymentBundle\Entity\Payment: { doctrine: orm }
    payments:
        ...
        payu_gateway:
            payu:
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
        $paymentName = 'payu_gateway';

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

        $captureToken = $captureToken = $this->get('payum.security.token_factory')
            ->createCaptureToken(
                $paymentName,
                $payment,
                'acme_payment_done' // the route to redirect after capture;
            );

        return new RedirectResponse($captureToken->getTargetUrl());
    }
```

### Handling gateway response

```php
    public function doneAction(Request $request)
    {
        $token = $this->get('payum.security.http_request_verifier')->verify($request);
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

acme_payment.listener.update_payment_status:
        class: Acme\PaymentBundle\Event\Listener\UpdatePaymentStatus
        tags:
          - { name: kernel.event_listener, event: payum.gateway.post_execute, method: updateStatus }
```

