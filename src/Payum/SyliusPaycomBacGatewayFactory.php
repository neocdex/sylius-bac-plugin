<?php

declare(strict_types=1);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace AltF4\SyliusPaycomBacPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use AltF4\SyliusPaycomBacPlugin\Payum\Action\StatusAction;
use AltF4\SyliusPaycomBacPlugin\Payum\Action\AuthorizeAction;
use AltF4\SyliusPaycomBacPlugin\Payum\Action\CaptureUsingCreditCardAction;

//use AltF4\SyliusPaycomBacPlugin\Payum\Action\ConvertPaymentAction;
/**
 * Description of SyliusPaycomBacGatewayFactory
 *
 * @author smolina
 */
class SyliusPaycomBacGatewayFactory extends GatewayFactory {
    
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name'=> 'paycom_bac',
            'payum.factory_title'=> 'Paycom Bac',
            //'payum.template.layout' => '@PayumCore\layout.html.twig',
            //'payum.template.obtain_credit_card' => '@PayumSymfonyBridge\obtainCreditCard.html.twig',
            //'payum.paths' => [
                //'PayumSymfonyBridge' => dirname((new \ReflectionClass(ReplyToSymfonyResponseConverter::class))->getFileName()).'/Resources/views',
            //],
            //'payum.action.obtain_credit_card' => new Reference('payum.action.obtain_credit_card_builder'),
            //'payum.action.obtain_credit_card' => new ObtainCreditCardAction(),
            'payum.action.capture'=> new CaptureUsingCreditCardAction(), 
            'payum.action.authorize'=> new AuthorizeAction(),                       
            'payum.action.status' => new StatusAction(),
            //'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);
        
        if(false == $config['payum.api']){
            
//            $config['payum.default_options'] = [
//                'username' => '',
//                'secret_key' => '',
//                'key_id' => '',
//                'merchant_id' => ''
//            ];
//            $config->defaults($config['payum.default_options']);
            
            $config['payum.required_options'] = ['username', 'secret_key', 'key_id', 'merchant_id'];
            
            $config['payum.api'] = function(ArrayObject $config){
                $config->validateNotEmpty($config['payum.required_options']);
                return new PaycomApi((array)$config, $config['payum.http_client'], $config['httplug.message_factory']);            
            };
        }            
    }
}
