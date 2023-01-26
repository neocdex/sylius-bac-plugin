<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AltF4\SyliusPaycomBacPlugin\Payum\Action;

use AltF4\SyliusPaycomBacPlugin\Payum\PaycomApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\LogicException;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\ObtainCreditCard;
//use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Sylius\Component\Core\Model\PaymentInterface;
use Webmozart\Assert\Assert;

/**
 * Description of AuthorizeAction
 *
 * @author smolina
 */
class AuthorizeAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface {
    use ApiAwareTrait;
    use GatewayAwareTrait;
    
    public function __construct()
    {
        $this->apiClass = PaycomApi::class;
    }
     /**
     * {@inheritdoc}
     */
    public function execute($request)
    {        
        RequestNotSupportedException::assertSupports($this, $request);     
        
//        $fd = fopen("/home/smolina/development/webapps/altf4/logs/bac.log","a");
//        fwrite($fd, "\nExecuting AuthorizeAction");     
               
        $model = ArrayObject::ensureArrayObject($request->getModel());
        
        //fwrite($fd, serialize($model));
        
        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, PaymentInterface::class);
        
        //check this, must be coherent with the api
        $modelFields = array('card');
        $cardFields = array(
            'holder',
            'number',
            'cvv',
            'validitydate'
        );
        
        if(false=== $model->validateNotEmpty($modelFields, false) && false == $model['ALIAS']){
//            fwrite($fd, "Enter validateNotEmpty()");
            try{
                $obtainCreditCard = new ObtainCreditCard($request->getToken());
                $obtainCreditCard->setModel($request->getFirstModel());
                $obtainCreditCard->setModel($request->getModel());
                $this->gateway->execute($obtainCreditCard);
                
                $card = $obtainCreditCard->obtain();
                
                if($card->getToken()){
                    $model['ALIAS'] = $card->getToken();
                }else{
                    $model['card'] = new ArrayObject(array(
                                    'holder'=>$card->getHolder(),
                                    'number'=>$card->getNumber(),
                                    'validitydate'=>$card->getExpireAt()->format('my'),
                                    'cvv'=>$card->getSecurityCode(),
                    ));
                }                
            } catch (RequestNotSupportedException $e){
                throw new LogicException('Credit card details has to be set explicitly or there has to be an action that supports ObtainCreditCard request.');
            }
        }
        if(false === ($model['ALIAS'] || $model->validateNotEmpty($modelFields,false))){           
            throw new LogicException('Either credit card fields or its alias has to be set.');
        }
//        fclose($fd);
        $model['order_id'] = $payment->getOrder()->getNumber();
        $model['amount'] = number_format($payment->getAmount()/100, 2);
        
        $result = $this->api->authorizePayment($model->toUnsafeArray());
        $model->replace((array)$result);
    }
    
    public function supports($request) {
        return
            $request instanceof Authorize &&
            $request->getModel() instanceof \ArrayAccess;//OrderInterface;
    }
}
