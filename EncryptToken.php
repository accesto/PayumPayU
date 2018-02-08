<?php

namespace Accesto\Component\Payum\PayU;

use Payum\Core\Request\Generic;

/**
 * Class EncryptToken.
 */
class EncryptToken extends Generic
{
    public function __construct($token)
    {
        parent::__construct(['value' => $token]);
    }
}
