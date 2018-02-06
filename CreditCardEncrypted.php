<?php

namespace Accesto\Component\Payum\PayU;

use Payum\Core\Model\CreditCardInterface;

interface CreditCardEncrypted extends CreditCardInterface
{
    public function getEncryptedToken();

    public function getSalt();
}
