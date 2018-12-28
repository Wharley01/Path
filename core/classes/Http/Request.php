<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 7/14/2018
 * Time: 4:42 AM
 */

namespace Path\Http;


class Request
{
    public $METHOD;
    public $server;
    public $params;
    public $inputs;

    public function __construct(){
        $this->METHOD = @$_SERVER["REQUEST_METHOD"];
        if($_SERVER['REQUEST_METHOD']==='POST' && empty($_POST)) {
            $_REQUEST = array_merge($_REQUEST,json_decode(file_get_contents('http://php://input')));
        }
        $this->inputs = @$_REQUEST;

        if(!@$_SERVER['REDIRECT_URL'])
            $_SERVER['REDIRECT_URL'] = "/";

        $this->server = (object)$_SERVER;
    }
    public function fetch($key){
        return @$_REQUEST[$key];
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    public function file($name){
        return @$_FILES[$name];
    }

    /**
     * @param $method
     * @param null $fallback
     * @return object
     */
    public static function MiddleWare(
        $method,
        $fallback = null
    ){
        $fallback = is_callable($fallback) ? $fallback:$fallback;
        return (object)["method" => $method,"fallback" => $fallback];
    }


}