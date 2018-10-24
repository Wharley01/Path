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
use Path\Utilities;

class Router
{
    public $request;
    private $assigned_paths = [//to hold all paths assigned

    ];
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
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
        }));
        $b_path = array_values(array_filter(explode("/",$path),function ($p){
            return strlen(trim($p)) > 0;
        }));
        $matched = 0;
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

    /**
     * @param $param
     * @param $raw_param
     * @param $path
     * @return bool
     * @throws RouterException
     */
    public static function type_check($param, $raw_param, $path){//throw exception if specified type doesn't match the dynamic url(url from the browser)
        if(strpos($raw_param,":") > -1){
            $type = strtolower(explode(":",$raw_param)[1]);
            switch ($type){
                case "int":
                    if(!preg_match("/^\d+$/",$param))
                        throw new RouterException("{$param} is not a {$type} in {$path}");
                    break;
                case "float":
                    if(!preg_match("/^\d+\.\d+$/",$param))
                        throw new RouterException("{$param} is not a {$type} in {$path}");
                    break;
                default:
                    $type = str_replace("]","",str_replace("[","",$type));
                    if(!preg_match("/{$type}/",$param))
                        throw new RouterException("{$param} does not match {$type} Regex in {$path}");

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
     * @return object
     */
    public static function get_params(
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
                self::type_check($b_real_path[$i],$raw_param,$path_str);
                $params[$param] = $b_real_path[$i];

            }
        }
        return (object) $params;
    }
    public static function MiddleWare(
        $method,
        $fallback = null
    ){
        return (object)["method" => $method,"fallback" => $fallback];
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
        if(!is_null($middle_ware)){
            if(!is_callable($middle_ware->method))
                throw new RouterException("Expected middleware method to be callable");
            if($middle_ware->fallback != null || $middle_ware->fallback){
                if(!$middle_ware->fallback instanceof Response)
                    throw new RouterException("Expected middleware method to be callable");
            }
            if(!$middle_ware->method(self::get_params($real_path,$path)))
                return false;
        }

            //        Set the path to list of paths
            $this->assigned_paths[] = [
                "path"      => $path,
                "method"    => $method
            ];
//        TODO: check if path contains a parameter path/$id
            if(self::compare_path($real_path,$path)){
                $c = $callback(self::get_params($real_path,$path));//call the callback, pass the params generated to it to be used
                if($c instanceof Response){//Check if return value from callback is a Response Object
                    self::set_header($c->headers);
                    echo $c->content;
                }elseif($c AND !$c instanceof Response){
                    throw new RouterException("Expecting an instance of Response to be returned at \"GET\" -> \"$path\"");
                }
            }



    }
    public function GET($path,$callback,$middle_ware = null){
        if(strtoupper($this->request->METHOD) == "GET") {
            $this->response("GET", $path, $callback,$middle_ware);
        }
        return $this;
    }
    public function POST($path,$callback,$middle_ware = null){
        if(strtoupper($this->request->METHOD) == "POST") {
            $this->response("POST", $path, $callback, $middle_ware);
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
    public function Fallback($callback){//executes when no route is specified
        if($this->should_fall_back()){//check if the current request doesn't match any request
//            print_r($this->assigned_paths);
            $c = $callback($this->request);//call the callback, pass the params generated to it to be used
            if($c instanceof Response){//Check if return value from callback is a Response Object
                $headers = $c->headers;
                foreach ($headers as $header => $value){
                    header("{$header}: {$value}");
                }
                echo $c->content;
            }elseif($c AND !$c instanceof Response){
                throw new RouterException("Expecting an instance of Response to be returned at \"Fallback\"");
            }
        }

    }


}