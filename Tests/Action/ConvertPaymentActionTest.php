<?php

namespace Accesto\Component\Payum\PayU\Tests\Action;

use Accesto\Component\Payum\PayU\Action\ConvertPaymentAction;
use Accesto\Component\Payum\PayU\Action\StatusAction;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Request\Convert;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Tests\Action\GatewayAwareActionTest;

/**
 * Class ConvertPaymentActionTest
 * @package Accesto\Component\Payum\PayU\Tests\Action
 */
class ConvertPaymentActionTest extends GatewayAwareActionTest
{
    protected $requestClass = 'Payum\Core\Request\Convert';
    protected $actionClass = 'Accesto\Component\Payum\PayU\Action\ConvertPaymentAction';

    /**
     * @test
     */
    public function shouldBeSubClassOfGatewayAwareAction()
    {
        $rc = new \ReflectionClass(ConvertPaymentAction::class);
        $this->assertTrue($rc->isSubclassOf(GatewayAwareAction::class));
    }

    /**
     * @test
     */
    public function shouldConvertModelToOrder()
    {
        $payment = $this->getMock(
            'Payum\Core\Model\PaymentInterface',
            array(
                'getTotalAmount',
                'getCurrencyCode',
                'getNumber',
                'getDescription',
                'getClientEmail',
                'getClientId',
                'getDetails',
                'getCreditCard',
                'setDetails'
            )
        );
        $payment->method('getCreditCard')->with()->willReturn(null);
        $payment->method('getTotalAmount')->with()->willReturn(100);
        $payment->method('getCurrencyCode')->with()->willReturn('PLN');
        $payment->method('getNumber')->with()->willReturn(1);
        $payment->method('getDescription')->with()->willReturn('test');
        $payment->method('getClientEmail')->with()->willReturn('test@example.com');
        $payment->method('getClientId')->with()->willReturn(111);
        $payment->method('getDetails')->with()->willReturn(array(
            'firstName' => 'John',
            'lastName' => 'Doe'
        ));

        $request = new Convert($payment, 'array');
        $action = new ConvertPaymentAction();
        $action->execute($request);

        $details = $request->getResult();

        $this->assertEquals(100, $details['totalAmount']);
        $this->assertEquals('PLN', $details['currencyCode']);
        $this->assertEquals(1, $details['extOrderId']);
        $this->assertEquals('test', $details['description']);
        $this->assertEquals('test@example.com', $details['client_email']);
        $this->assertEquals(111, $details['client_id']);
        $this->assertEquals('John', $details['buyer']['firstName']);
        $this->assertEquals('Doe', $details['buyer']['lastName']);
        $this->assertEquals('test@example.com', $details['buyer']['email']);
        $this->assertEquals('NEW', $details['status']);
    }
}
