<?php

class Pocker{
    private static $instance = null;
    private $guzzleClient = null;

    private $host = 'localhost';
    private $port = '4201';
    private $apiVersion = 'v1.39';

    private function __construct(){
        $this->guzzleClient = new GuzzleHttp\Client();
    }

    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new Pocker();
        }
        
        return self::$instance;
    }

    public function getInfo(){
        $res = $this->guzzleClient->request('GET', 'http://192.168.111.129:4201/info');
        echo $res->getStatusCode();
        echo $res->getHeader('content-type')[0];
        echo $res->getBody();
    }
}
