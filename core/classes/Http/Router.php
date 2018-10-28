<?php
/**
 * @Author Sulaiman.Adewale
 * @File Router
 * @Date: 10/22/2018
 * @Time: 12:14 AM
 */

namespace Path\Http;
load_class(["Utilities"]);

use Path\Http\Request;
use Path\RouterException;
use Path\Utilities;

class Router
{
    public  $request;
    private $assigned_paths = [//to hold all paths assigned

    ];
    private $exception_callback;
    public function __construct(Request $request)
    {
        $this->request = $request;
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
            if($i >= count($b_path)) {//if the amount of url path is more than required, return false
                return false;
            }
            $path = $b_path[$i];
            if(!is_null($b_path[$i]) && @$path[0] == "@" && !is_null($b_real_path[$i])){
                $matched += 1;
            }elseif (!is_null($b_path[$i]) &&  !is_null($b_real_path[$i]) && $b_path[$i] == $b_real_path[$i]){
                $matched += 1;
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
                            $exception_callback = call_user_func_array($this->exception_callback,[$this->request,$error]);
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
                            $exception_callback = call_user_func_array($this->exception_callback,[$this->request,$error]);
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
                    $type = str_replace("]","",str_replace("[","",$type));
                    if(!preg_match("/{$type}/",$param)){//Check if the regex match the URL parameter
                        $error = ["msg" => "{$param} does not match {$type} Regex in {$path}","path" => $path];
                        if (is_callable($this->exception_callback)) {
                            $exception_callback = call_user_func_array($this->exception_callback,[$this->request,$error]);
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
    public static function get_param_name($raw_param){
        $param = explode(":",$raw_param);
        return $param[0];
    }
    /**
     * @param $real_path
     * @param $path
     * @return bool|object
     */
    public  function get_params(
        $real_path,
        $path
    ){
        $path_str = $path;
        $b_real_path = explode("/",$real_path);
        $b_path = explode("/",$path);
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
                if(!$this->type_check($b_real_path[$i],$raw_param,$path_str)){//if type check doesn't match don't return any param
                    return false;
                }
                $params[$param] = $b_real_path[$i];

            }
        }
        return (object) $params;
    }

    /**
     * @param $method
     * @param $path
     * @param $callback
     * @param null $middle_ware
     * @return bool
     * @throws RouterException
     */
    private function response(
        $method,
        $path,
        $callback,
        $middle_ware = null
    ){
        $real_path = trim($this->request->server->REDIRECT_URL);
        $params = $this->get_params($real_path,$path);
        if(!self::compare_path($real_path,$path))//if the browser path doesn't match specified path template
            return false;


        $this->assigned_paths[] = [
            "path"      => $path,
            "method"    => $method
        ];
        if(!is_null($middle_ware)){
            if(!class_implements($middle_ware->method)['Path\Http\MiddleWare'])
                throw new RouterException("Expected \"{$middle_ware->method}\" to implement \"MiddleWare\" interface in \"{$path}\"");
            $fallback = null;
            if($middle_ware->fallback != null || $middle_ware->fallback) {
                $fallback = ($middle_ware->fallback) ? $middle_ware->fallback : null;
                $fallback = is_callable($fallback) ?  $fallback($this->request,$params): null;
            }

                if($fallback != null && !$fallback instanceof Response)
                    throw new RouterException("Expected middleware method to be instance of Response");

//            Check middle ware return
            $check_middle_ware = (new $middle_ware->method())->Control($params,$params);
            if(!$check_middle_ware){//if the middle ware class returns false
                if(!is_null($fallback)){//if there is a fallback function parsed
                    http_response_code($fallback->status);//set response code
                    self::set_header($fallback->headers);//set header
                    echo $fallback->content;
                    return true;
                }
                return true;
            }
        }

            //        Set the path to list of paths

//        TODO: check if path contains a parameter path/$id
            if(self::compare_path($real_path,$path)){
                $c = $callback($params);//call the callback, pass the params generated to it to be used
                if($c instanceof Response){//Check if return value from callback is a Response Object
                    http_response_code($c->status);
                    self::set_header($c->headers);
                    echo $c->content;
                }elseif($c AND !$c instanceof Response){
                    throw new RouterException("Expecting an instance of Response to be returned at \"GET\" -> \"$path\"");
                }
            }
            return true;
    }

    /**
     * @param $path
     * @param $callback
     * @return $this
     */
    public function GET($path, $callback){
        if(is_array($path)){//check if path is associative array or a string
            $_path = @$path['path'];
            $_middle_ware = @$path['middleware'];
        }else{
            $_path = $path;
            $_middle_ware = null;
        }
        $real_path = trim($this->request->server->REDIRECT_URL);
//Check if path is the one actively visited in browser
        if(strtoupper($this->request->METHOD) == "GET" && self::compare_path($real_path,$_path)) {
            $this->response("GET", $_path, $callback,$_middle_ware);
        }
        return $this;
    }
    public function POST($path,$callback){
        if(is_array($path)){
            $_path = @$path['path'];
            $_middle_ware = @$path['middleware'];
        }else{
            $_path = $path;
            $_middle_ware = null;
        }
        $real_path = trim($this->request->server->REDIRECT_URL);
        if(strtoupper($this->request->METHOD) == "POST" && self::compare_path($real_path,$_path)) {
            $this->response("POST", $_path, $callback, $_middle_ware);
        }
        return $this;
    }
    private function should_fall_back(){
        $real_path = trim($this->request->server->REDIRECT_URL);
        $current_method = strtoupper($this->request->METHOD);
        for ($i = 0;$i < count($this->assigned_paths);$i++){
            if(self::compare_path($real_path,$this->assigned_paths[$i]['path']) && $this->assigned_paths[$i]['method'] == $current_method) return false;
        }
        return true;
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
            $c = $callback($this->request);//call the callback, pass the params generated to it to be used
            if($c instanceof Response){//Check if return value from callback is a Response Object
                http_response_code($c->status);
                self::set_header($c->headers);
                echo $c->content;
            }elseif($c AND !$c instanceof Response){
                throw new RouterException("Expecting an instance of Response to be returned at \"Fallback\"");
            }
        }

    }



}