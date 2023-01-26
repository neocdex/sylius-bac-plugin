<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AltF4\SyliusPaycomBacPlugin\Payum\Action;

use AltF4\SyliusPaycomBacPlugin\Payum\PaycomApi;
//use GuzzleHttp\Client;
//use GuzzleHttp\Exception\RequestException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
//use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Payum\Core\Request\Capture;
//use Payum\Core\Request\ObtainCreditCard;
//use Payum\Core\Security\SensitiveValue;

/**
 * Description of CaptureUsingCreditCardAction
 *
 * @author smolina
 */
class CaptureUsingCreditCardAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    
    public function __construct()
    {
        $this->apiClass = PaycomApi::class;
    }
    
    public function setApi($api): void
    {
        if (!$api instanceof PaycomApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . PaycomApi::class);
        }

        $this->api = $api;
    } 
    
    public function execute($request) {
        RequestNotSupportedException::assertSupports($this, $request);        
//      
        //$details = $request->getModel();
        
//        $fd = fopen("/home/smolina/development/webapps/altf4/logs/bac.log","a");
//        fwrite($fd, "\nExecuting CaptureUsingCreditCardAction");
//        fclose($fd);      
        
        
        /** @var SyliusPaymentInterface $payment */
        $model = $request->getModel();
        //$model = ArrayObject::ensureArrayObject($request->getModel());
        
        if(isset($model['transactionid']) && isset($model['authorized']))
        {
            $result = $this->api->capturePayment($model);
            $request->setResult((array)$result);
        }
        
    }   
    
    public function supports($request) {
        return $request instanceof Capture &&
                $request->getModel() instanceof \ArrayAccess;
    }
}
