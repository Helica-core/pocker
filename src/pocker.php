<?php
require '../vendor/autoload.php';

class Pocker{
    private static $instance = null;
    private $guzzleClient = null;

    private $host = '192.168.111.193';
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
 
    private function getURLBase(){
        return 'http://' . $this->host . ':' . $this->port . '/' . $this->apiVersion;
    }

    private function requestDocker($path, $method, $data=null){
        //TODO: chack 
        //      api version control
        if($data != null){
            $res = $this->guzzleClient->request($method, $this->getURLBase() . $path, ['json' => $data]);
        }else{
            $res = $this->guzzleClient->request($method, $this->getURLBase() . $path);
        }
        echo 'status code: ' . $res->getStatusCode()."\n";

        $stream = $res->getBody();
        echo "content:\n".$stream->getContents()."\n";

        //TODO: error handle
        return json_decode($res->getBody());
    }

    private function streamRequestDocker($path, $method, $data=null){
        $res = $this->guzzleClient->request($method, $this->getURLBase() . $path, ['stream' => true, 'json' => $data]);
        $body = $res->getBody();

        echo 'status code: ' . $res->getStatusCode()."\n";
        $stream = $res->getBody();

        echo $stream->isWritable() ? "write able \n" : "can't write \n";
        echo "content:\n".$stream->getContents()."\n";

        while(!$stream->eof()){
            echo $stream->read(1024);
            //$body->write('echo hello world');
            sleep(1);
        }
    }

    public function getInfo(){
        $method = 'GET';
        $path = '/info';
        return $this->requestDocker($path, $method);
    }

    public function setConfig($host, $port, $apiVersion){
        $this->host = $host;
        $this->port = $port;
        $this->apiVersion = $apiVersion;
    }

    public function buildImage(){
        
    }

    public function listImage(){
        $path = '/images/json';
        $method = 'GET';

        return $this->requestDocker($path, $method);
    }

    public function listContainer(){
        $method = 'GET';
        $path = '/containers/json';

        return $this->requestDocker($path, $method);
    }

    /*
        新建container
        TODO: container的各项配置
    */
    public function createContainer($imageName, $cmd){
        $path = '/containers/create';
        $method = 'POST';
        //TODO: more features support
        $data = json_decode('{
            "Hostname": "test",
            "User": "root",
            "AttachStdin": false,
            "AttachStdout": false,
            "AttachStderr": false,
            "Tty": false,
            "OpenStdin": false,
            "StdinOnce": false,
            "Image": "$imageName",
            "Cmd":[
                ""
            ]
          }');
        $data->Image = $imageName;
        $data->Cmd = $cmd;

        return $this->requestDocker($path, $method, $data);
    }

    public function startContainer($containerId){
        $path   = "/containers/$containerId/start";
        $method = 'POST';
        $data   = null;
        
        return $this->requestDocker($path, $method);
    }

    public function stopContainer(){

    }

    public function killContainer(){

    }

    public function inspectContainer(){

    }

    /*
        attach到container上
        貌似websocket方式好一点。
        http劫持的方式还没有调研好。
    */
    public function attachContainer($containerId){
        $path   = "/containers/$containerId/attach";
        $method = "POST";
        $data = json_decode('{
        }');

        $this->streamRequestDocker($path, $method);
    }

    /*
        获取container日志
        获取日志功能似乎只能获取主进程的stdout.
    */
    public function getContainerLogs($containerId){
        $path = "/containers/$containerId/logs";
        $method = "GET";
        $parameters = "stdout=1";

        return $this->requestDocker($path.'?'.$parameters, $method);
    }

    /*
        创建并执行一条指令。
    */
    public function execContainer($containerId, $cmd){
        $path = "/containers/$containerId/exec";
        $method = 'POST';
        $data = json_decode('{
            "AttachStdin":false,
            "AttachStdout":false,
            "AttachStderr":false, 
            "Tty":true,
            "Cmd": ["/bin/sh", "/root/x.sh"]
        }');

        //$data->Cmd = [$cmd];
        var_dump($data);

        //create exec object
        $res = $this->requestDocker($path, $method, $data);
        $execId = $res->Id;

        //exec
        $path = "/exec/$execId/start";
        $method = "POST";
        $data = json_decode('{
            "Detach": false,
            "Tty": true
        }');

        $this->streamRequestDocker($path, $method, $data);

        //inspect
        $path = "/exec/$execId/json";
        $method = "GET";

        //$res = $this->requestDocker($path, $method);

        //echo $res;
    }
}
