<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/16/2019
 * @Time 1:45 PM
 * @Project Path
 */

namespace Path\Http;


use Path\Cache;
use Path\Controller;
use Path\Controller\Live\TestLive;
use Path\LiveController;
use Path\Sessions;
use Path\WatcherException;

import(
    "core/Classes/Http/Response",
    "core/Classes/LiveController",
    "core/Classes/Cache"
    );

class Watcher
{
    private $path;
    private $watcher_namespace = "Path\Controller\Live\\";
    private $watchers_path = "Path/Controllers/Live/";
    private $cache = [];
    private $response = [];
    private $response_instance;
    public $throw_exception = false;
    public $error = null;
    private $controller_data = [
        "root"          => null,
        "watchable_methods"    => null,
        "params"        => null
    ];
    public $has_changes = false;
    /*
     * This will be set to true at first execution
     * so watcher will execute at least once after every initiation
     * */
    public $has_executed = false;
    /*
     * Holds all controllers being watched
     * */
    private $controller;
    public function __construct(string $path)
    {
        $this->path = trim($path);
        $this->response_instance = new Response();
        $this->extractInfo();
    }
    private function getResources($payload):array {
        $split_load = explode("&",$payload);
        $all_params = [];
        $watchable_methods = [];
        foreach ($split_load as $load){
            if(preg_match("/Params=\[(.+)\]/i",$load,$matches)){
                $params = explode(",",$matches[1]);
                foreach ($params as $param){
                    $param = explode("=",$param);
                    $all_params[$param[0]] = $param[1];
                }
            }

            if (preg_match("/Watch=\[(.+)\]/i",$load,$matches)){
                $list = $matches[1];
                $list = explode(",",$list);
                $watchable_methods = $list;
            }
        }

        return ['params' => $all_params,'watchable_methods' => $watchable_methods];
    }
    private function extractInfo(){
        $path = $this->path;
        $path = array_values(array_filter(explode("/",$path),function ($p){
            return strlen(trim($p)) > 0;
        }));
        $payload = array_slice($path,1);
        $path = trim($path[0]);
        $url_resources = $this->getResources($payload[0] ?? "");
        $this->controller_data['root']                 = $path;
        $this->controller_data['watchable_methods']    = $url_resources['watchable_methods'];
        $this->controller_data['params']               = $url_resources['params'];
        $this->controller = $this->getController();
    }
    private static function isValidClassName($class_name){
        return preg_match("/^[\w]+$/",$class_name);
    }
    public function getController():?LiveController{
        $path = $this->controller_data['root'];
        if(!self::isValidClassName($path)){
            $this->throwException("Invalid LiveController Name \"{$path}\" ");
        }


        if($path){
            import($this->watchers_path.$path);
            $path = $this->watcher_namespace.$path;

            $controller = new $path(
                $this->response_instance,
                $this->controller_data['params']
            );

            return $controller;
        }
        return null;
    }
    private function getWatchable(){

    }
    public function watch(){
        $controller = $this->getController();
        if(!isset($controller->watch_list)){
            $this->throwException("Specify \"watch_list\" in ". get_class($controller));
        }


        $watch_list = self::castToString($controller->watch_list);
//        cache watch_list values if not already cached
        $this->execute($watch_list,$controller);
        $this->cache($watch_list);

    }

    private static function castToString($arr){
        $ret = [];
        foreach ($arr as $key => $value){
            $ret[$key] = (string) $value;
        }
        return $ret;
    }
    private static function shouldCache($method,$value){
        $_value = Cache::get($method);
        return is_null($_value) || $_value !== $value;
    }



    private function cache($watch_list){
        foreach ($watch_list as $method => $value){
            if(self::shouldCache($method,$value)){
//                echo "caching {$method} to {$value}".PHP_EOL;
//                var_dump($value);
                Cache::set($method,$value);
            }
        }
    }

    private  function shouldExecute($method,$value){
        $cached_value = Cache::get($method);
        return (is_null($cached_value) || $cached_value != $value) || !$this->has_executed;
    }

    public function execute($watch_list,$controller){
        $watchable_methods = $this->controller_data['watchable_methods'];
//        var_dump($_SESSION);

        if(is_null($watchable_methods)){
//            watch all watchable
        }else{
//            validate the watchlist
            foreach ($watchable_methods as $method){
                $_method = $method;
                $_value = $watch_list[$method];
                if($this->shouldExecute($_method,$_value)){
                    $this->has_changes = true;
                    $this->has_executed = true;
                    if(!method_exists($controller,$_method)){
                        $this->response[$_method] = $_value;
                    }else{
                        $response = $controller->{$_method}();
                        $this->response[$_method] = $response;
                    }
                }else{
                    $this->has_changes = false;

                }
            }
        }
    }
    private function throwException($error_text){
        if($this->throw_exception){
            throw new WatcherException($error_text);
        }else{
            $this->error = $error_text;
        }
    }
    public function getResponse(){
        return $this->response;
    }
    public static function log($text){
        echo PHP_EOL.$text.PHP_EOL;
        flush();
        ob_flush();
    }
}