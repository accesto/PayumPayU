<?php

namespace Accesto\Component\Payum\PayU\Exception;

use Payum\Core\Exception\Http\HttpException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PayUException
 * @package Accesto\Component\Payum\PayU\Exception
 */
class PayUException extends HttpException
{
    public static function newInstance($status)
    {
        $label = 'PayUException';

        $parts = array(
            $label
        );
        if (property_exists($status, 'statusLiteral')) {
            $parts[] = '[reason literal] ' . $status->statusLiteral;
        }
        if (property_exists($status, 'statusCode')) {
            $parts[] = '[status code] ' . $status->statusCode;
        }
        if (property_exists($status, 'statusDesc')) {
            $parts[] = '[reason phrase] ' . $status->statusDesc;
        }
        $message = implode(PHP_EOL, $parts);

        $e = new static($message);

        return $e;
    }
}
