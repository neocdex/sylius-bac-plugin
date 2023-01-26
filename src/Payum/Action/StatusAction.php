<?php

declare(strict_types=1);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace AltF4\SyliusPaycomBacPlugin\Payum\Action;

use AltF4\SyliusPaycomBacPlugin\Payum\PaycomApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\ApiAwareInterface;
//use Payum\Core\Bridge\Spl\ArrayObject;
/**
 * Description of StatusAction
 *
 * @author smolina
 */
class StatusAction implements ActionInterface, ApiAwareInterface {
    use ApiAwareTrait;
    
    public function __construct()
    {
        $this->apiClass = PaycomApi::class;
    }
    
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
//        $fd = fopen("/home/smolina/development/webapps/altf4/logs/bac.log","a");
//        fwrite($fd, "\nExecuting StatusAction");
        
        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $details = $payment->getDetails();
        $model = $request->getModel();

        
        if(isset($model['response_code'])){
            $response_code = $model['response_code'];
            //$authorized = isset($model['authorized'])?$model['authorized']:false;
            $completed = isset($model['completed'])?$model['completed']:false;
            if(100 == $response_code /*&& $authorized*/){
                //if received hash is ok, then authorized
                $this->api->verifyHash((array)$model)?$request->markAuthorized():$request->markFailed();
            }
            elseif(100 == $response_code && $completed) {
                $request->markCaptured();           
            }
            elseif(($response_code >= 200 && $response_code < 300) || $response_code >= 300){
                $request->markFailed();           
            }else{
                $request->markUnknown();
            }
        }        
    }
    
    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
    
}
