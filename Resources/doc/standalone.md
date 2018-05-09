## Instalation
add to your composer.json
```json
{
    "require": {
        "accesto/payum-pay-u": "dev-master"
    }
}
```

## Usage
### Configuration

```php
<?php
// config.php

include 'vendor/autoload.php';

use Payum\Core\PayumBuilder;
use Payum\Core\Payum;
use Payum\Core\Model\Payment;
use Accesto\Component\Payum\PayU\PayUGatewayFactory;

$paymentClass = Payment::class;

/** @var Payum $payum */
$payum = (new PayumBuilder())
    ->addDefaultStorages()
    ->addGatewayFactoryConfig('payu_gateway_factory', [
        'environment' => 'secure',
        'pos_id' => '145227',
        'signature_key' => '13a980d4f851f3d9a1cfc792fb1f5e50'
    ])
    ->addGatewayFactory(
        'payu_gateway_factory',
        function(array $config, \Payum\Core\GatewayFactoryInterface $coreGatewayFactory) {
            return new PayUGatewayFactory($config, $coreGatewayFactory);
        }
    )
    ->addGateway('payu_gateway', array(
        'factory' => 'payu_gateway_factory'
    ))->getPayum()
;
```

### Usage

```php
<?php
// prepare.php

include 'config.php';

$gatewayName = 'payu_gateway';

$storage = $payum->getStorage($paymentClass);

$payment = $storage->create();
$payment->setNumber(uniqid());
$payment->setCurrencyCode('PLN');
$payment->setTotalAmount(1000); // 10 PLN
$payment->setDescription('A description');
$payment->setClientId('anId');
$payment->setClientEmail('foo@example.com');

$payment->setDetails(array(
    'firstName' => 'John',
    'lastName' => 'Doe'
));


$storage->update($payment);

$captureToken = $payum->getTokenFactory()->createCaptureToken($gatewayName, $payment, 'done.php');

header("Location: ".$captureToken->getTargetUrl());

```

```php
<?php
// capture.php

include 'config.php';

use Payum\Core\Request\Capture;
use Payum\Core\Reply\HttpRedirect;

$token = $payum->getHttpRequestVerifier()->verify($_REQUEST);
$gateway = $payum->getGateway($token->getGatewayName());

if ($reply = $gateway->execute(new Capture($token), true)) {
    if ($reply instanceof HttpRedirect) {
        header("Location: ".$reply->getUrl());
        die();
    }

    throw new \LogicException('Unsupported reply', null, $reply);
}

$payum->getHttpRequestVerifier()->invalidate($token);

header("Location: ".$token->getAfterUrl());
```

```php
<?php

// done.php

use Payum\Core\Request\GetHumanStatus;

include 'config.php';

$token = $payum->getHttpRequestVerifier()->verify($_REQUEST);
$gateway = $payum->getGateway($token->getGatewayName());

$gateway->execute($status = new GetHumanStatus($token));
$payment = $status->getFirstModel();

header('Content-Type: application/json');
echo json_encode(array(
    'status' => $status->getValue(),
    'order' => array(
        'total_amount' => $payment->getTotalAmount(),
        'currency_code' => $payment->getCurrencyCode(),
        'details' => $payment->getDetails(),
    ),
));
```

### Handling instant payment notification

```php
<?php
use Payum\Core\Request\Notify;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Reply\ReplyInterface;

include 'config.php';

$token = $payum->getHttpRequestVerifier()->verify($_REQUEST);
$gateway = $payum->getGateway($token->getGatewayName());

try {
    $gateway->execute(new Notify($token));

    http_response_code(204);
    echo 'OK';
} catch (HttpResponse $reply) {
    foreach ($reply->getHeaders() as $name => $value) {
        header("$name: $value");
    }

    http_response_code($reply->getStatusCode());
    echo ($reply->getContent());

    exit;
} catch (ReplyInterface $reply) {
    throw new \LogicException('Unsupported reply', null, $reply);
}
```

Create extension in order to perform some specific action on payment status update

```php
<?php

use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetStatusInterface;

class PaymentStatusExtension implements ExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function onPostExecute(Context $context)
    {
        $request = $context->getRequest();
        if (false == $request instanceof Generic) {
            return;
        }
        if (false == $request instanceof GetStatusInterface) {
            return;
        }

        $payment = $request->getFirstModel();

        if (false == $payment instanceof PaymentInterface) {
            return;
        }
        
        if ($request->isCaptured()) {
            // perform some action
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onPreExecute(Context $context)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function onExecute(Context $context)
    {
    }
}
```

And add following line to config.php

```php
$payum->getGateway('payu_gateway')->addExtension(new PaymentStatusExtension());
```
