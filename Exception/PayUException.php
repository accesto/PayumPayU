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
    private $model;

    public static function newInstance($status = null, $model = null)
    {
        $label = 'PayUException';

        $parts = array(
            $label
        );
        if ($status) {
            if (property_exists($status, 'statusLiteral')) {
                $parts[] = '[reason literal] ' . $status->statusLiteral;
            }
            if (property_exists($status, 'statusCode')) {
                $parts[] = '[status code] ' . $status->statusCode;
            }
            if (property_exists($status, 'statusDesc')) {
                $parts[] = '[reason phrase] ' . $status->statusDesc;
            }
        }
        $message = implode(PHP_EOL, $parts);

        $e = new static($message);
        $e->model = $model;

        return $e;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }
}
