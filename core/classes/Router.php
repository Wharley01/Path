<?php
/**
 * @Author Sulaiman.Adewale
 * @File Router
 * @Date: 10/22/2018
 * @Time: 12:14 AM
 */

namespace Path;


use Path\Request;

class Router
{
    public $request;
    private $assigned_paths = [//to hold all paths assigned

    ];
    public function __construct(Request $request)
    {
        $this->request = $request;
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
    public static function get_params($real_path, $path){
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
    private function response($method,$path,$callback){
        if(strtoupper($this->request->METHOD) == $method){
            //        TODO: check if path contains a parameter path/$id
            $real_path = trim($this->request->server->REDIRECT_URL);
            if(self::compare_path($real_path,$path)){
                $c = $callback(self::get_params($real_path,$path));//call the callback, pass the params generated to it to be used
                if($c instanceof Response){//Check if return value from callback is a Response Object
                    $headers = $c->headers;

                    foreach ($headers as $header => $value){
                        header("{$header}: {$value}");
                    }
                    echo $c->content;
                }else{
                    throw new RouterException("Expecting an instance of Response to be returned at \"GET\" -> \"$path\"");
                }
            }
        }
    }
    public function GET($path,$callback){
        $this->response("GET",$path,$callback);
    }
    public function POST($path,$callback){
        $this->response("POST",$path,$callback);
    }


}