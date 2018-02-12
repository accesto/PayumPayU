<?php

namespace Accesto\Component\Payum\PayU\Tests\Action;

use Accesto\Component\Payum\PayU\Action\CaptureAction;
use Accesto\Component\Payum\PayU\Action\SetPayUAction;
use Accesto\Component\Payum\PayU\OpenPayUWrapper;
use Accesto\Component\Payum\PayU\SetPayU;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Token;
use Payum\Core\Request\Capture;
use Payum\Core\Tests\GenericActionTest;

/**
 * Class SetPayUActionTest
 * @package Accesto\Component\Payum\PayU\Tests\Action
 */
class SetPayUActionTest extends GenericActionTest
{
    protected $requestClass = SetPayU::class;
    protected $actionClass = SetPayUAction::class;
    /**
     * @test
     */
    public function shouldBeSubClassOfGatewayAwareAction()
    {
        $rc = new \ReflectionClass(CaptureAction::class);
        $this->assertTrue($rc->implementsInterface('Payum\Core\Action\ActionInterface'));
    }

    /**
     * @test
     * @expectedException \Payum\Core\Reply\HttpRedirect
     */
    public function shouldRedirectAfterSuccess()
    {
        $createResponse = new \stdClass();
        $createResponse->status = new \stdClass();
        $createResponse->status->statusCode = 'SUCCESS';
        $createResponse->orderId = 1;
        $createResponse->redirectUri = 'example.com';

        $action = $this->setupSetPayUAction($createResponse);
        $t = new Token();
        $t->setGatewayName('payu');
        $t->setDetails(new \ArrayObject());
        $request = new SetPayU($t);
        $request->setModel(new ArrayObject());
        $action->execute($request);
    }

    /**
     * @test
     * @expectedException \Accesto\Component\Payum\PayU\Exception\PayUException
     */
    public function shouldThrowExceptionAfterFailure()
    {
        $createResponse = new \stdClass();
        $createResponse->status = new \stdClass();
        $createResponse->status->statusCode = 'Error';
        $createResponse->orderId = 1;
        $createResponse->redirectUri = 'example.com';

        $action = $this->setupSetPayUAction($createResponse);
        $t = new Token();
        $t->setGatewayName('payu');
        $t->setDetails(new \ArrayObject());
        $request = new SetPayU($t);
        $request->setModel(new ArrayObject());
        $action->execute($request);
    }

    /**
     * @param $createResponse
     * @return SetPayUAction
     */
    protected function setupSetPayUAction($createResponse)
    {
        $response = $this->getMock('\OpenPayU_Result', array('getResponse'));
        $response->method('getResponse')
            ->with()
            ->willReturn($createResponse);

        $openpayu = $this->getMock('Accesto\Component\Payum\PayU\OpenPayUWrapper', array('create', 'retrieve'), array(
            'secure',
            '13a980d4f851f3d9a1cfc792fb1f5e50',
            '145227',
            '145227',
            '13a980d4f851f3d9a1cfc792fb1f5e50',
        ));
        $openpayu->method('create')->with($this->anything())->willReturn(
            $response
        );

        $tokenFactory = $this->getMock(
            'Payum\Core\Bridge\Symfony\Security\TokenFactory',
            array(),
            array(
                $this->getMock('Payum\Core\Storage\StorageInterface'),
                $this->getMock('Payum\Core\Registry\StorageRegistryInterface'),
                $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface'),
            )
        );
        $token = $this->getMock('TokenInterface', array('getTargetUrl'));

        $token->method('getTargetUrl')
            ->with()
            ->willReturn('example.com');

        $genericTokenFactory = $this->getMock(
            'Payum\Core\Security\GenericTokenFactory',
            array('createNotifyToken'),
            array($tokenFactory, array())
        );

        $genericTokenFactory->method('createNotifyToken')
            ->with($this->equalTo('payu'), $this->anything())
            ->willReturn($token);

        $action = new SetPayUAction();
        $action->setApi(array(
            'environment' => 'secure',
            'pos_id' => '145227',
            'signature_key' => '13a980d4f851f3d9a1cfc792fb1f5e50',
            'oauth_client_id' => '145227',
            'oauth_secret' => '13a980d4f851f3d9a1cfc792fb1f5e50',
        ));
        $action->setGenericTokenFactory($genericTokenFactory);
        $action->setOpenPayUWrapper($openpayu);
        return $action;
    }
}
