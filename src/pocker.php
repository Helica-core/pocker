<?php

class Pocker{
    private static $instance = null;
    private $guzzleClient = null;

    private $host = '127.0.0.1';
    private $port = '4201';
    private $apiVersion = 'v1.40';

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
        try{
            if($data != null){
                $res = $this->guzzleClient->request($method, $this->getURLBase() . $path, ['json' => $data]);
            }else{
                $res = $this->guzzleClient->request($method, $this->getURLBase() . $path);
            }
        }catch(Exception $e){
            echo '[!] Guzzle Error \n' . $e->getMessage();
            #var_dump($e);
            return null;
        }

        return $this->returnData($res);
    }

    private function streamRequestDocker($path, $method, $data=null){
        try{
            $res = $this->guzzleClient->request($method, $this->getURLBase() . $path, ['stream' => true, 'json' => $data]);

        }catch (Exception $e){
            echo '[!] Guzzle Error \n' . $e->getMessage();
            #var_dump($e);
            return null;
        }
        $stream = $res->getBody();

        $data = '';
        while(!$stream->eof()){
            $data .= $stream->read(1024);
            sleep(0.1);
        }

        $rtn['code'] = $res->getStatusCode();
        $rtn['data'] = $data;

        return $rtn;
    }

    private function returnData($res){
        $rtn = array();
        $rtn['code'] = $res->getStatusCode();
        $rtn['data'] = json_decode($res->getBody());

        return $rtn;
    }

    public function getInfo(){
        $method = 'GET';
        $path = '/info';
        $rtn = $this->requestDocker($path, $method);

        if($rtn['code'] == 200){
            $rtn['success'] = true;
        }

        return $rtn;
    }

    public function setConfig($host, $port, $apiVersion){
        $this->host = $host;
        $this->port = $port;
        $this->apiVersion = $apiVersion;
    }

    public function buildImage($dockerfilePath, $tag){    //通过传文件的方式 tar.gz
        $path = '/build?t='.$tag.'&networkmode=bridge';
        $method = 'POST';

        $res = $this->guzzleClient->request($method, $this->getURLBase() . $path, [
            'headers' => [
                'Content-Type' => 'application/tar'
            ],
            'body' => fopen($dockerfilePath, 'r'),
            'stream' => true
        ]);
        $stream = $res->getBody();
        $data = '';
        while(!$stream->eof()){
            $data .= $stream->read(1024);
            sleep(0.1);
        }

        $rtn['code'] = $res->getStatusCode();
        $rtn['data'] = $data;

        if($rtn['code'] == 200){
            $rtn['success'] = true;
        }
        return $rtn;
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

        $rtn = $this->requestDocker($path, $method, $data);
        if($rtn['code'] == 201){
            $rtn['success'] = true;
        }

        return $rtn;
    }

    public function startContainer($containerId){
        $path   = "/containers/$containerId/start";
        $method = 'POST';
        $data   = null;

        $res = $this->requestDocker($path, $method);
        if($res['code'] == 204){
            $res['success'] = true;
        }

        return $res;
    }

    public function stopContainer($containerId){
        $path   = "/containers/$containerId/stop?t=1";
        $method = 'POST';
        $data   = null;
        $rtn = $this->requestDocker($path, $method);

        if($rtn['code'] == 204 || $rtn['code'] == 304){
            $rtn['success'] = true;
        }
        return $rtn;
    }

    public function killContainer($containerId){
        $path   = "/containers/$containerId/kill";
        $method = 'POST';
        $data   = null;
        
        return $this->requestDocker($path, $method);
    }

    public function removeContainer($containerId){
        $path   = "/$containerId";
        $method = 'DELETE';
        $rtn = $this->requestDocker($path, $method);

        if($rtn['code'] == 204){
            $rtn['success'] = true;
        }
        return $rtn;
    }
    public function inspectContainer($containerId){
        $path   = "/containers/$containerId/json";
        $method = 'GET';
        $data   = null;

        $rtn = $this->requestDocker($path, $method);
        if($rtn['code'] == 200){
            $rtn['success'] = true;
        }
        return $rtn;
    }

    /*
        attach到container上
        貌似websocket方式好一点。
        http劫持的方式还没有调研好。
    */
    public function attachContainer($containerId){
        $path   = "/containers/$containerId/attach";
        $method = "POST";

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
            "AttachStdout":true,
            "AttachStderr":true, 
            "Tty":true,
            "Cmd": ""
        }');
        $data->Cmd = $cmd;

        //create exec object
        $res = $this->requestDocker($path, $method, $data);
        $execId = $res['data']->Id;

        //exec
        $path = "/exec/$execId/start";
        $method = "POST";
        $data = json_decode('{
            "Detach": false,
            "Tty": true
        }');
        $res = $this->streamRequestDocker($path, $method, $data);
        if($res['code'] == 200){
            $res['success'] = true;
        }

        //inspect
        $path = "/exec/$execId/json";
        $method = "GET";
        $res['inspect'] = $this->requestDocker($path, $method);

        return $res;
    }
}
