<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AltF4\SyliusPaycomBacPlugin\Payum;

use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\Http\HttpException;
//use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
/**
 * Description of PaycomApi
 *
 * @author smolina
 */
final class PaycomApi {
    public const VERSION = 'v1.0';
    
    public const OP_AUTHORIZATION = "auth";
    public const OP_SALE = "sale";
    
    
    private const API_ENDPOINT = 'https://paycom.credomatic.com/PayComBackEndWeb/common/requestPaycomService.go';
    
    /** @var HttpClientInterface */
    protected $client;
    
    /** @var MessageFactory */
    protected $messageFactory;
    
    /** @var ArrayObject|array */
    protected $options = [];

    public function __construct(array $config, HttpClientInterface $client, MessageFactory $messageFactory)
    //public function __construct(string $config)
    {
        $config = ArrayObject::ensureArrayObject($config);
        $config->defaults($this->options);
        $config->validateNotEmpty(array(
            'username', 'key_id', 'secret_key', 'merchant_id'
        ));
        $this->options = $config;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }
    
    public function getUsername(): string
    {
        return $this->options['username'];
    }
    public function getKeyId(): string
    {
        return $this->options['key_id'];
    }
    
    public function getSecreyKey(): string
    {
        return $this->options['secret_key'];
    }
    
    public function getMerchantId(): int
    {
        return $this->options['merchant_id'];
    }
    
    public function getOption(string $option, $default = '')
    {
        return $this->options[$option] ?? $default;
    }
    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }
    
    public function getMessageFactory(): MessageFactory
    {
        return $this->messageFactory;
    }
    
    //revisar que retornara la fx
    public function authorizePayment($params):array
    {
        $params = ArrayObject::ensureArrayObject($params);
        //$params->validateNotEmpty(['card', 'amount']);
        
        $time = time();
        //check creditcard information
        $fields = array(
            'username'=> $this->getOption('username'),
            'type'=>self::OP_AUTHORIZATION,
            'key_id'=> $this->getOption('key_id'),
            'hash'=> $this->md5Hash(array($params['order_id'], $params['amount'], $time, $this->getOption('secret_key'))),
            'time'=>$time,
            'ccnumber'=> $params['card']['number'],
            'ccexp'=> $params['card']['validitydate'],
            'amount'=>$params['amount'],
            'orderid'=>$params['order_id'],
            'cvv'=>$params['card']['cvv']
        );
        
        
        $response = $this->doRequest('POST', $fields);        
        
        $data = $this->convertResponseToArray($response);
        
        //$data['authorized'] = true;
        
        return $data;
    }
    
    public function capturePayment($params): array
    {
        $params = ArrayObject::ensureArrayObject($params);
        $params->validateNotEmpty(['order_id', 'transactionid', 'amount']);   
        $time = time();
        
        $fields = array(
            'username'=> $this->getOption('username'),
            'type'=>self::OP_SALE,
            'key_id'=> $this->getOption('key_id'),
            'hash'=> md5Hash(array($params['order_id'], $params['amount'], $time, $this->getOption('secret_key'))),
            'time'=> $time,            
            'transactionid'=>$params['transactionid'],
            'amount'=>$params['amount'],
            'orderid'=>$params['order_id'],
        );
        
        
        $response = $this->doRequest('POST', $fields);
        $data = $this->convertResponseToArray($response);
        $data['completed'] = true;
        
        return $data;
    }
    
    private function convertResponseToArray(ResponseInterface $response):array
    {
        $responsePayload = [];
        //if($response->hasHeader('location')){
            $body = ltrim($response->getBody(), "?");            
            parse_str($body, $responsePayload);
            $fd = fopen("/home/smolina/development/webapps/altf4/logs/bac.log","a");
            fwrite($fd, "\nExecuting API::convertResponseToArray()");        
            fwrite($fd, serialize($responsePayload));
//            if(is_array($body)){
//                $responsePayload = $body;
//            }                
//            else { 
//                                
//            }
//            if(!$this->verifyHash($responsePayload)){
//                throw new LogicException("Invalid checksum in response from server.");
//            }            
        //}
        
        return $responsePayload;
    }
    
    /**
     * Verify if the hash of the given parameter is correct
     *
     * @param array $params
     *
     * @return bool
     */
    public function verifyHash(array $params): bool
    {
        $fd = fopen("/home/smolina/development/webapps/altf4/logs/bac.log","a");
        fwrite($fd, "\nExecuting API::verifyHash()\n");         
        fwrite($fd, json_encode($params));

        fwrite($fd, "empty(hash) = ".empty($params['hash']));
        if (empty($params['hash'])) {
            return false;
        }
        
        $hash = $params['hash'];
        unset($params['hash']);
        $calcHash = $this->calculateHash($params);
        fwrite($fd, serialize($calcHash));
        fwrite($fd, serialize($hash));
        fclose($fd);

        return $hash === $this->calculateHash($params);
    }
    
    public function calculateHash(array $params): string
    {
        
        $fields = array(
            'orderid'=>$params['order_id'], 
            'amount'=>$params['amount'], 
            'response'=>$params['response'], 
            'transactionid'=>$params['transactionid'],
            'avsresponse'=>$params['avsresponse'],
            'cvvresponse'=>$params['cvvresponse'],
            'time'=>$params['time'],
            'secret_key'=>$this->getOption('secret_key')
        );
        
        //$fields = array_intersect_key($params, array_flip($keysAllowed));        
//        $fd = fopen("/home/smolina/development/webapps/altf4/logs/bac.log","a");
//        fwrite($fd, "\nExecuting API::calculateHash()");        
//        fwrite($fd, serialize($fields));
//        fwrite($fd, "\n");
//        fclose($fd);
        return $this->md5Hash($fields);
    }

    private function md5Hash(array $fields): string
    {
        return md5(implode('|', $fields));
    }
    /**
     * @param string method, array $fields
     *
     * @return ResponseInterface
     */
    protected function doRequest($method, array $fields): ResponseInterface
    {
        $headers = [
            'Content-Type'=> 'application/x-www-form-urlencoded'
        ];

        $request = $this->messageFactory->createRequest(
                $method, 
                $this->getApiEndpoint(), 
                $headers, 
                http_build_query($fields, '', '&'));

//        $fd = fopen("/home/smolina/development/webapps/altf4/logs/bac.log","a");
//        fwrite($fd, "\nExecuting API::doRequest()\n");  
//        $body = $request->getBody();
//        $payload = [];
//        parse_str($body, $payload);
//        fwrite($fd, serialize($payload));
        $response = $this->client->send($request);
        $statusCode = $response->getStatusCode();
        if (false == ($statusCode >= 200 && $statusCode < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response;
    }
     /**
     * @return string
     */
    protected function getApiEndpoint(): string
    {
        return self::API_ENDPOINT;
    }
}
