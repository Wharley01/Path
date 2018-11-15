<?php
/**
 * @Author Sulaiman.Adewale
 * @File Router
 * @Date: 10/22/2018
 * @Time: 12:14 AM
 */

namespace Path\Http;
load_class(["Utilities","Http/Request","Database","Connection"]);

use Connection\Mysql;
use Data\Database;
use Path\Http\Request;
use Path\RouterException;
use Path\Utilities;
//use Path\Controller\User;

class Router
{
    public  $request;
    public $root_path;
    private $database;
    private $response_instance;
    private $assigned_paths = [//to hold all paths assigned

    ];
    private $exception_callback;//Callback exception

    const VALID_REQUESTS = [//Accepted request type
        "GET",
        "POST",
        "PUT",
        "PATCH",
        "DELETE",
        "COPY",
        "HEAD",
        "OPTIONS",
        "LINK",
        "UNLINK",
        "PURGE",
        "LOCK",
        "PROPFIND",
        "VIEW"
    ];

    /**
     * Router constructor.
     * @param string $root_path
     */
    public function __construct($root_path = "/")
    {
        $this->root_path = $root_path;
        $this->request = new Request();
        $this->database = new Database(new Mysql());
        $this->response_instance = new Response();
    }

    /**
     * set header from associative array
     * @param array $headers
     */
    private static function set_header(array $headers){
        foreach ($headers as $header => $value){
            header("{$header}: $value");
        }
    }
    private static function path_matches($param,$raw_param){
//        echo $param,$raw_param;
        $raw_param = substr($raw_param,1);
        if(strpos($raw_param,":") > -1){
            $type = strtolower(explode(":",$raw_param)[1]);
            switch ($type){
                case "int":
                    if(!preg_match("/^\d+$/",$param)){
                        return false;
                    }
                    break;
                case "float":
                    if(!preg_match("/^\d+\.\d+$/",$param)){
                        return false;
                    }
                    break;
                default:
                    $type = preg_replace("/\}$/","",preg_replace("/^\{/","",$type));
                    if(!preg_match("/{$type}/",$param)){//Check if the regex match the URL parameter
                    return false;
                    }

                    break;
            }

        }
        return true;
    }
    private static function is_root($real_path, $path){
        $b_real_path = array_values(array_filter(explode("/",$real_path),function ($p){
            return strlen(trim($p)) > 0;
        }));//get all paths in a array, filter
        $b_path = array_values(array_filter(explode("/",$path),function ($p){
            return strlen(trim($p)) > 0;
        }));
//        var_dump($b_real_path,$b_path);
        if($real_path == $path)
            return true;

        $matches = 0;
        for($i = 0;$i < count($b_path); $i ++){//loop through the path template instead of real  path

            $c_path = trim($b_path[$i]);
            $c_real_path = trim($b_real_path[$i]);
//            echo $c_real_path,"/",$c_path;
            if($c_path == $c_real_path){//if current templ path == browser path, count as matched
                $matches++;
            }elseif ($c_path != $c_real_path && $c_path[0] == "@" && self::path_matches($c_real_path,$c_path)){//if current path templ not equal to current raw path, and current path templ is is param, and the param obeys the restriction add to match count
                $matches++;
            }
        }
//        echo var_dump($matches == count($b_path));
        return $matches == count($b_path);

    }
    /**
     * Compare browser path and path template to
     * @param $real_path
     * @param $path
     * @return bool
     */
    public static function compare_path($real_path, $path){
        /*
         * $path holds the path template
         * $real_path holds the path from the browser
         */
        $b_real_path = array_values(array_filter(explode("/",$real_path),function ($p){
            return strlen(trim($p)) > 0;
        }));//get all paths in a array, filter
        $b_path = array_values(array_filter(explode("/",$path),function ($p){
            return strlen(trim($p)) > 0;
        }));
        if($real_path == $path){//if the path are literally the same, don't do much hard job, return true
            return true;
        }
//        Else, continue checking

        $matched = 0;//number of matched paths(Both template and path
        for($i = 0;$i < count($b_real_path); $i ++){
            if($i > count($b_path)) {//if the amount of url path is more than required, return false
                return false;
            }
//            Continue execution
            $c_path = @$b_path[$i];//current path (template)
            $c_real_path = @$b_real_path[$i];//current path (from web browser)
            if($c_path == $c_real_path){// current template path is equal to real path from browser count it as matched
                $matched++;//count
            }elseif ($c_path != $c_real_path && $c_path[0] == "@" && !!$c_path && $c_real_path){//if path template is not equal to current path, and real path
                $matched++;
            }else{
                return false;
            }
        }
        return $matched == count($b_path);

    }

    private function write_response($response){
        if(!$response instanceof Response)
            throw new RouterException("Callback function expected to return an instance of Response Class");
        http_response_code($response->status);//set response code
        self::set_header($response->headers);//set header
        echo $response->content;
        return true;
    }

    /**
     * @param $param
     * @param $raw_param
     * @param $path
     * @return bool
     * @throws RouterException
     */
    public function type_check($param, $raw_param, $path){//throw exception if specified type doesn't match the dynamic url(url from the browser)
        if(strpos($raw_param,":") > -1){
            $type = strtolower(explode(":",$raw_param)[1]);
            switch ($type){
                case "int":
                    if(!preg_match("/^\d+$/",$param)){
                        $error = ["msg" => "{$param} is not a {$type} in {$path}","path" => $path];
                        if (is_callable($this->exception_callback)) {
                            $exception_callback = call_user_func_array($this->exception_callback,[$this->request,$this->response_instance,$error]);
                        } else {
                            $exception_callback = false;
                        }
                        if($exception_callback){
                            $this->write_response($exception_callback);
                            return false;
                        }else{
                            throw new RouterException($error['msg']);
                        }
                    }
                    break;
                case "float":
                    if(!preg_match("/^\d+\.\d+$/",$param)){
                        $error = ["msg" => "{$param} is not a {$type} in {$path}","path" => $path];
                        if (is_callable($this->exception_callback)) {
                            $exception_callback = call_user_func_array($this->exception_callback,[$this->request,$this->response_instance,$error]);
                        } else {
                            $exception_callback = false;
                        }
                        if($exception_callback){
                            $this->write_response($exception_callback);
                            return false;
                        }else{
                            throw new RouterException($error['msg']);
                        }
                    }

                    break;
                default:
                    $type = preg_replace("/\}$/","",preg_replace("/^\{/","",$type));
                    if(!preg_match("/{$type}/",$param)){//Check if the regex match the URL parameter
                        $error = ["msg" => "{$param} does not match {$type} Regex in {$path}","path" => $path];
                        if (is_callable($this->exception_callback)) {
                            $exception_callback = call_user_func_array($this->exception_callback,[$this->request,$this->response_instance,$error]);
                        } else {
                            $exception_callback = false;
                        }
                        if($exception_callback){
                            $this->write_response($exception_callback);
                            return false;
                        }else{
                            throw new RouterException($error['msg']);
                        }
                    }


                    break;

            }

        }
        return true;
    }
    private function concat_path($root,$path){
        return $root.$path;
    }
    public static function get_param_name($raw_param){
        $param = explode(":",$raw_param);
        return $param[0];
    }
    /**
     * @param $real_path
     * @param $path
     * @return bool|object
     */
    public function get_params(
        $real_path,
        $path
    ){
        $path_str = $path;
        $b_real_path = array_values(array_filter(explode("/",$real_path),function ($p){
            return strlen(trim($p)) > 0;
        }));
        $b_path = array_values(array_filter(explode("/",$path),function ($p){
            return strlen(trim($p)) > 0;
        }));;

        $params = [];


        for($i = 0;$i < count($b_real_path); $i ++){
            if($i >= count($b_path)) {//if the amount of url path is more than required, return false
                return (object)$params;
            }
            $path = $b_path[$i];
            if(!is_null($b_path[$i]) && @$path[0] == "@" && !is_null($b_real_path[$i])){
//                TODO: check for string typing
                $raw_param = $path;
                $param = self::get_param_name(substr($path,1,strlen($path)));
                if(!self::path_matches($b_real_path[$i],$raw_param)){//if type check doesn't match don't return any param
                    return false;
                }
                $params[$param] = $b_real_path[$i];

            }
        }
        return (object) $params;
    }

    /**
     * @param $method
     * @param $root
     * @param $path
     * @param $callback
     * @param null $middle_ware
     * @param bool $is_group
     * @return bool
     * @throws RouterException
     */
    private function response(
        $method,
        $root,
        $path,
        $callback,
        $middle_ware = null,
        $is_group = false
    ){
        $path = $this->concat_path($this->root_path,$path);
        $real_path = trim($this->request->server->REDIRECT_URL);
        $params = $this->get_params($real_path,$path);
        if(!self::compare_path($real_path,$path) && !$is_group) {//if the browser path doesn't match specified path template
            echo "Erroroooooo";
            return false;
        }

        $this->assigned_paths[$root][] = [
            "path"      => $path,
            "method"    => $method,
            "is_group"  => $is_group
        ];
        if(!is_null($middle_ware)){
            if(!class_implements($middle_ware->method)['Path\Http\MiddleWare'])
                throw new RouterException("Expected \"{$middle_ware->method}\" to implement \"MiddleWare\" interface in \"{$path}\"");
            $fallback = null;
            if($middle_ware->fallback != null || $middle_ware->fallback) {
                $fallback = ($middle_ware->fallback) ? $middle_ware->fallback : null;
                $request = new Request();
                $request->params = $params;
                $fallback = is_callable($fallback) ?  $fallback($this->request,$this->response_instance): null;
            }

                if($fallback != null && !$fallback instanceof Response)
                    throw new RouterException("Expected middleware method to be instance of Response");

//            Check middle ware return
            $request = new Request();
            $request->params = $params;
            $check_middle_ware = (new $middle_ware->method())->Control($request,$this->response_instance);
            if(!$check_middle_ware){//if the middle ware class returns false
                if(!is_null($fallback)){//if there is a fallback function parsed
                    $this->write_response($fallback);
                    return true;
                }
                return true;
            }
        }

            //        Set the path to list of paths

//        TODO: check if path contains a parameter path/$id
//            Check if method calling response is
                if($is_group){
                    $router = new Router($path);
                    $c = $callback($router);//call the callback, pass the params generated to it to be used
                }else{
                    $request = new Request();
                    $request->params = $params;
                    if(is_string($callback)){
                        $_callback = $this->break_controller($callback);
                        $class = $_callback->ini_class->{$_callback->method}($request,$this->response_instance);
                        if($class instanceof Response){//Check if return value from callback is a Response Object
                            $this->write_response($class);
                        }

                    }else{
                        $c = $callback($request,$this->response_instance);
                        if($c instanceof Response){//Check if return value from callback is a Response Object
                            $this->write_response($c);
                        }elseif($c AND !$c instanceof Response){
                            throw new RouterException("Expecting an instance of Response to be returned at \"GET\" -> \"$path\"");
                        }
                    }
                    //call the callback, pass the params generated to it to be used
                }



    }

    /**
     * @return bool
     */
    private function should_fall_back(){
        $real_path = trim($this->request->server->REDIRECT_URL);
        $current_method = strtoupper($this->request->METHOD);
        foreach ($this->assigned_paths as $root => $paths){
            $root = $root == "/" ? "":$root;
            for ($i = 0;$i < count($this->assigned_paths);$i++){
//                var_dump($root.$paths[$i]['path']);
                if($paths[$i]['is_group'] AND self::is_root($real_path,$paths[$i]['path'])){
                    return false;
                }
                if(self::compare_path($real_path,$root.$paths[$i]['path']) && ($paths[$i]['method'] == $current_method && $paths[$i]['method'] != "ANY")) return false;
            }
        }

        return true;
    }

    public function group($path, $callback){
        if(is_array($path)){//check if path is associative array or a string
            $_path = @$path['path'];
            $_middle_ware = @$path['middleware'];
        }else{
            $_path = $path;
            $_middle_ware = null;
        }
        $real_path = trim($this->request->server->REDIRECT_URL);
        if(self::is_root($real_path,$_path)){
            $this->response("ANY","/",$_path,$callback,$_middle_ware,true);
        }
    }
    private function break_controller($controller_str){
//        break string
        if(!preg_match("/([\S]+)\-\>([\S]+)/",$controller_str))
            throw new RouterException("Invalid Router String");

//        Break all string to array
        $contr_breakdown = array_values(array_filter(explode("->",$controller_str),function ($m){
            return strlen($m) > 0;
        }));//filter empty array
        $class_ini = $contr_breakdown[0];
        load_class($class_ini,"controllers");

//        load_class($class_ini,"controllers");
        $class_ini = "Path\Controller\\".$class_ini;
        $class_ini = new $class_ini();

        return (object)["ini_class" => $class_ini,"method" => $contr_breakdown[1]];
    }
    private function process_request($path,$callback,$method){
        if(is_array($path)){//check if path is associative array or a string
            $_path = @$path['path'];
            $_middle_ware = @$path['middleware'];
        }else{
            $_path = $path;
            $_middle_ware = null;
        }
        $real_path = trim($this->request->server->REDIRECT_URL);
//Check if path is the one actively visited in browser
        if(strtoupper($this->request->METHOD) == $method && self::compare_path($real_path,$this->root_path.$_path)) {

//            Check if $callback is a string, parse appropriate
            $this->response($method,"/", $_path, $callback,$_middle_ware);
        }
    }

    /**
     * @param $path
     * @param $callback
     * @return $this
     */
    public function GET($path, $callback){
        $this->process_request($path,$callback,"GET");
        return $this;
    }
    public function POST($path,$callback){
        $this->process_request($path,$callback,"POST");
        return $this;
    }

    public function ExceptionCatch($callback){
        if(!is_callable($callback))
            throw new RouterException("ExceptionCatch expects a callable function");

        $this->exception_callback = $callback;
        return true;
    }
    public function Error404($callback){//executes when no route is specified
        if($this->should_fall_back()){//check if the current request doesn't match any request
//            print_r($this->assigned_paths);
            $c = $callback($this->request,$this->response_instance);//call the callback, pass the params generated to it to be used
            if($c instanceof Response){//Check if return value from callback is a Response Object

                $this->write_response($c);
            }elseif($c AND !$c instanceof Response){
                throw new RouterException("Expecting an instance of Response to be returned at \"Fallback\"");
            }
        }

    }



}