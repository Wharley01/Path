<?php
/**
 * @Author Sulaiman.Adewale
 * @File Router
 * @Date: 10/22/2018
 * @Time: 12:14 AM
 */

namespace Path\Core\Http;

use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Error\Exceptions;
use Path\Core\Router\Route\Controller;


class Router
{
    public  $request;
    public  $root_path;
    private $database;
    private $response_instance;
    private $build_path = "/dist/";
    private $controllers_path = "path/Controllers/Route/";
    private $controllers_namespace = "Path\\App\\Controllers\\Route\\";
    private $middleware_path = "path/Http/MiddleWares/";
    private $middleware_namespace = "Path\\App\\Http\\MiddleWare\\";
    private $assigned_paths = [ //to hold all paths assigned

    ];
    private $exception_callback; //Callback exception

    const VALID_REQUESTS = [ //Accepted request type
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
    public $real_path;

    public $scheme;

    public $host;

    public $sub_domain;
    /**
     * Router constructor.
     * @param string $root_path
     */
    public function __construct($root_path = "/")
    {
        $this->root_path = $root_path;
        $this->request = new Request();
        $this->database = null;
        $path_parse = parse_url($this->request->server->REQUEST_URI ?? $this->request->server->REDIRECT_URL);
        $this->real_path = $path_parse["path"];
        $this->host = $path_parse["host"] ?? $_SERVER["HTTP_HOST"];
        $this->scheme = $path_parse["scheme"] ?? (@$_SERVER["HTTPS"] ? "https":"http");
        //        TODO: Initialize model for database
        $this->response_instance = new Response($this->build_path);
    }

    public function setBuildPath($path)
    {
        $this->build_path = $path;
        $this->response_instance = new Response($this->build_path);
    }

    /**
     * set header from associative array
     * @param array $headers
     */
    private static function set_header(array $headers)
    {
        foreach ($headers as $header => $value) {
            header("{$header}: $value");
        }
    }

    private static function isRoot($real_path, $path, &$params = [])
    {

        $b_real_path = array_values(array_filter(explode("/", $real_path), function ($p) {
            return strlen(trim($p)) > 0;
        })); //get all paths in a array, filter
        $b_path = array_values(array_filter(explode("/", $path), function ($p) {
            return strlen(trim($p)) > 0;
        }));

        if ($real_path == $path)
            return true;

        $matches = 0;
        for ($i = 0; $i < count($b_path); $i++) { //loop through the path template instead of real  path

            $c_path_value = trim(@$b_path[$i]);
            $c_real_path_value = trim(@$b_real_path[$i]);
            if ($c_path_value == $c_real_path_value) { //if current templ path == browser path, count as matched
                $matches++;
            } elseif ($c_path_value != $c_real_path_value && $c_path_value[0] == "@" && self::regexMatches($c_real_path_value, substr($c_path_value, 1))) { //if current path templ not equal to current raw path, and current path templ is is param, and the param obeys the restriction add to match count
                $key = self::getParamName($c_path_value);
                //                $params[$key]
                $matches++;
            }
        }

        return $matches == count($b_path);
    }

    /**
     * Compare browser path and path template to
     * @param $real_path
     * @param $path
     * @param array $params
     * @return bool
     */
    public static function pathMatches($real_path, $path, &$params = [])
    {


        if ($real_path == $path) { //if the path are literally the same, don't do much hard job, return true
            return true;
        }

        /*
        * $path holds the path template
        * $real_path holds the path from the browser
        */
        $b_real_path = array_values(array_filter(explode("/", $real_path), function ($p) {
            return strlen(trim($p)) > 0;
        })); //get all paths in a array, filter
        $b_path = array_values(array_filter(explode("/", $path), function ($p) {
            return strlen(trim($p)) > 0;
        }));

        //        Else, continue checking

        $matched = 0; //number of matched paths(Both template and path

        for ($i = 0; $i < count($b_real_path); $i++) {

            $c_path_value = trim(@$b_path[$i]); //current path value (template)
            $c_real_path_value = trim(@$b_real_path[$i]); //current path (from web browser)

            if ($c_path_value == "*"){
                return true;
            }

            //            var_dump($c_path_value[strlen($c_path_value)-1]);
            $is_optional = @$c_path_value[strlen($c_path_value) - 1] == '?'; //check if last character is question mark
            if ($is_optional) {
                $c_path_value = substr($c_path_value, 0, (strlen($c_path_value) - 1));
            }

            if ($i > count($b_path)) { //if the amount of url path is more than required, return false
                return false;
            }

            //            Continue execution

            if ($c_path_value == $c_real_path_value) { // current template path is equal to real path from browser count it as matched
                $matched++; //count
            } elseif (
                $c_path_value != $c_real_path_value &&
                @$c_path_value[0] == "@" &&
                isset($b_path[$i]) &&
                isset($b_real_path[$i]) &&
                self::regexMatches($c_real_path_value, substr($c_path_value, 1))
            ) { //if path template is not equal to current path, and real path
                $param_key = self::getParamName($c_path_value);
                $params[$param_key] = $c_real_path_value;
                $matched++;
            } else {
                return false;
            }
        }

        if ($b_path[0] == "*")
            return  true;

        return $matched == count($b_path);
    }

    private function writeResponse($response)
    {
        if (!$response instanceof Response)
            throw new Exceptions\Router("Callback function expected to return an instance of Response Class");
        http_response_code($response->status); //set response code
        self::set_header($response->headers); //set header
        if ($response->is_binary) {
            readfile($response->content);
        } else {
            print($response->content);
        }

        @ob_end_flush();
        @ob_flush();
        @flush();
        die();
    }

    private static function isFloat($value)
    {
        $value = floatval($value);
        return ($value && intval($value) != $value);
    }

    /**
     * @param $value
     * @param $path_param
     * @return bool
     * @internal param $param
     * @internal param $raw_param
     * @internal param $path
     */

    private static function regexMatches($value, $path_param)
    {
        if (self::isFloat($value)) {
            $value = floatval($value);
        } elseif (is_numeric($value)) {
            $value = (int)$value;
        }

        $split = explode(":", $path_param);
        $key = $split[0];

        if (isset($split[1])) {
            $type = $split[1];
            if ($type == "int") {
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return false;
                } else {
                    return true;
                }
            } elseif ($type === "string") {
                if (!is_string($value))
                    return false;
                else
                    return true;
            } elseif ($type === "float") {
                if (!self::isFloat($value))
                    return false;
                else
                    return true;
            } else {
                //
                if (!preg_match("/{$type}/", $value))
                    return false;
                else
                    return true;
            }
        } else {
            return true;
        }
    }

    public static function getParamName($raw_param)
    {
        $raw_param = substr($raw_param, 1);

        $param = explode(":", $raw_param);
        return $param[0];
    }

    /**
     * @param $middle_wares
     * @param $params
     * @param $_path
     * @param $real_path
     * @throws Exceptions\Router
     */
    private function validateAllMiddleWare($middle_wares, $params, $_path, $real_path)
    {
        if (!is_array($middle_wares) || is_string($middle_wares))
            $middle_wares = [$middle_wares];

        $request = new Request();
        $request->params = $params;
        foreach ($middle_wares as $middle_ware) {
            if ($middle_ware) {
                $middle_ware_name = explode("\\", $middle_ware);
                $middle_ware_name = $middle_ware_name[count($middle_ware_name) - 1];
                //            Load middleware class
                $ini_middleware = new $middle_ware();

                if ($ini_middleware instanceof MiddleWare) {
                    //            initialize middleware

                    //            Check middle ware return
                    $check_middle_ware = $ini_middleware->validate($request, $this->response_instance);

                    if ($check_middle_ware === false) { //if the middle ware control method returns false
                        //                call the fall_back response
                        $fallback_response = $ini_middleware->fallBack($request, $this->response_instance);
                        if (!is_null($fallback_response)) { //if user has a fallback method

                            if ($fallback_response && is_array($fallback_response)) {
                                $this->writeResponse($this->response_instance->json($fallback_response));
                                die();
                            } elseif($fallback_response instanceof Response) {
                                $this->writeResponse($fallback_response);
                                die();
                            }else{
                                return false;
                            }
                        }
                        die();
                    }
                } else {
                    throw new Exceptions\Router("Expected \"{$middle_ware->method}\" to implement \"Path\\Http\\MiddleWare\" interface in \"{$_path}\"");
                }
            }
        }
    }

    /**
     * @param $method
     * @param $root
     * @param $path
     * @param $callback
     * @param null $middle_ware
     * @param null $fallback
     * @param bool $is_group
     * @return bool
     * @throws Exceptions\Router
     * @internal param $_path
     */
    private function response(
        $method,
        $root,
        $path,
        $callback,
        $middle_ware = null,
        $fallback = null,
        $is_group = false
    ) {

        if (!$path || is_null($path))
            throw new Exceptions\Router("Specify Path for your router");

        $_path = $this::joinPath($root, $path);
        $real_path = trim($this->real_path);
        $params = [];
        if (!self::pathMatches($real_path, $_path, $params) && !$is_group) { //if the browser path doesn't match specified path template
            return false;
        }
        $params = (object)$params;


        $this->assigned_paths[$root][] = [
            "path"      => $_path,
            "method"    => $method,
            "is_group"  => $is_group
        ];

        $this->validateAllMiddleWare($middle_ware, $params, $_path, $real_path);

        //        Set the path to list of paths

        //        TODO: check if path contains a parameter path/$id
        //            Check if method calling response is
        if ($is_group) {
            $router = new Router($_path);
            $c = $callback($router); //call the callback, pass the params generated to it to be used
        } else {
            $request = new Request();
            $request->params = $params;
            if (is_string($callback) && strpos($callback, "->")) {
                $_callback = $this->breakController($callback, $params);

                try {
                    $class = $_callback->ini_class->{$_callback->method}($request, $this->response_instance);
                } catch (\Throwable $e) {
                    throw new Exceptions\Router($e->getMessage(), 0, $e);
                }
                if ($class instanceof Response) { //Check if return value from callback is a Response Object
                    $this->writeResponse($class);
                }elseif (is_array($class)){
                    $this->writeResponse($this->response_instance->json($class));
                }
            } else {
                /** ************************************************ */
                //Check if the callback is a Controller instance and call the response method with the $request and $response as parameter
                if (is_string($callback)) {
                    //                    check if the string is used to represent the class and import amd instantiate it
                    $callback = $this->importControllerFromString($callback, $params);
                }

                if ($callback instanceof \Closure) {
                    $c = $callback($request, $this->response_instance);
                }
                elseif ($callback instanceof Controller or method_exists($callback, 'response')) {
                    $c = $callback->response($request, $this->response_instance);
                }else {
                    throw new Exceptions\Router('Custom Class Object must have a response method');
                }
                //                $c = ! $callback instanceof \Closure ? $callback->response($request, $this->response_instance)
                //                    : $callback($request,$this->response_instance);

                if ($c instanceof Response) { //Check if return value from callback is a Response Object
                    $this->writeResponse($c);
                }elseif (is_array($c)){

                    $this->writeResponse($this->response_instance->json($c));
                } elseif ($c and !$c instanceof Response) {
                    throw new Exceptions\Router("Expecting an instance of Response or Array to be returned at \"GET\" -> \"$_path\"");
                }

            }
            //call the callback, pass the params generated to it to be used
        }
    }

    /**
     * @return bool
     */
    private function shouldFallBack()
    {
        $real_path = trim($this->real_path);
        $current_method = strtoupper($this->request->METHOD);

        foreach ($this->assigned_paths as $root => $paths) {
            $root = $root == "/" ? "" : $root;
            for ($i = 0; $i < count($this->assigned_paths); $i++) {
                if ($paths[$i]['is_group'] and self::isRoot($real_path, $paths[$i]['path'])) {
                    return false;
                }
                if (self::pathMatches($real_path, $root . $paths[$i]['path']) && ($paths[$i]['method'] == $current_method || $paths[$i]['method'] == "ANY")) {
                    return false;
                }
            }
        }

        return true;
    }
    static function joinPath($root, $path)
    {
        if ($root != "/") {
            $root = (strripos($root, "/") == (strlen($root) - 1)) ? $root : $root . "/";
        } else {
            $root = "";
        }

        $path = (strripos($path, "/") == 0) ? (substr_replace($path, "", 0, 0)) : $path;
        return $root . $path;
    }
    public function group($path, $callback)
    {

        if (is_array($path)) { //check if path is associative array or a string
            $_path = $path['path'] ?? null;
            $_middle_ware = $path['middleware'] ?? null;
            $_fallback = $path['fallback'] ?? null;
        } else {
            $_path = $path;
            $_middle_ware = null;
            $_fallback  = null;
        }

        $real_path = trim($this->real_path);
        if (self::isRoot($real_path, self::joinPath($this->root_path, $_path))) {
            $this->response("ANY", $this->root_path, $_path, $callback, $_middle_ware, $_fallback, true);
            die();
        }
    }
    private function breakController($controller_str, $params)
    {
        //        break string
        if (!preg_match("/([\S]+)\-\>([\S]+)/", $controller_str))
            throw new Exceptions\Router("Invalid Router String");

        //        Break all string to array
        $contr_breakdown = array_values(array_filter(explode("->", $controller_str), function ($m) {
            return strlen(trim($m)) > 0;
        })); //filter empty array
        $class_ini = $contr_breakdown[0];

        if (strpos($class_ini, "\\") === false)
            $class_ini = $this->controllers_namespace . $class_ini;

        //        load_class($class_ini,"controllers");

        try {
            $request = new Request();
            $request->params = $params;
            $class_ini = new $class_ini($request, $this->response_instance);
        } catch (\Throwable $e) {
            throw new Exceptions\Router($e->getMessage());
        }

        return (object)["ini_class" => $class_ini, "method" => $contr_breakdown[1]];
    }

    private function importControllerFromString($controller, $params)
    {

        $request = new Request();
        $request->params = $params;

        $controller_instance = new $controller($request, $this->response_instance);

        return $controller_instance;
    }
    private function processMultipleRequestPath($path, $callback, $method)
    {

        if (is_array($path)) { //check if path is associative array or a string
            $_path = $path['path'] ?? null;
            $_middle_ware = $path['middleware'] ?? null;
            $_fallback = $path['fallback'] ?? null;
        } else {
            $_path = $path;
            $_middle_ware = null;
            $_fallback = null;
        }
        if (is_string($_path)) {
            $_path = array_filter(explode("|", $_path), function ($path) {
                return strlen(trim($path)) > 0;
            });
        }
        if (is_null($_path))
            return;
        foreach ($_path as $each_path) {
            $this->processRequest(["path" => trim($each_path), "middleware" => $_middle_ware, "fallback" => $_fallback], $callback, $method);
        }
    }

    private function processRequest($path, $callback, $method)
    {

        if (is_array($path)) { //check if path is associative array or a string
            $_path = $path['path'] ?? null;
            $_middle_ware = $path['middleware'] ?? null;
            $_fallback = $path['fallback'] ?? null;
        } else {
            $_path = $path;
            $_middle_ware = null;
            $_fallback    = null;
        }
        $real_path = trim($this->real_path);
        //Check if path is the one actively visited in browser
        if ((strtoupper($this->request->METHOD) == $method || $method == "ANY")) {
            //            Check if $callback is a string, parse appropriate
            $this->response($method, $this->root_path, $_path, $callback, $_middle_ware, $_fallback);
        }
    }

    /**
     * @param $path
     * @param $callback
     * @return $this
     * @throws Exceptions\Router
     */
    public function get($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "GET");
        return $this;
    }
    public function any($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "ANY");
        return $this;
    }
    public function post($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "POST");
        return $this;
    }
    public function put($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "PUT");
        return $this;
    }
    public function patch($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "PATCH");
        return $this;
    }
    public function delete($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "DELETE");
        return $this;
    }
    public function copy($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "COPY");
        return $this;
    }
    public function head($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "HEAD");
        return $this;
    }
    public function options($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "HEAD");
        return $this;
    }
    public function link($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "LINK");
        return $this;
    }
    public function unlink($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "UNLINK");
        return $this;
    }
    public function purge($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "PURGE");
        return $this;
    }
    public function lock($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "LOCK");
        return $this;
    }
    public function propFind($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "PROPFIND");
        return $this;
    }
    public function view($path, $callback)
    {
        $this->processMultipleRequestPath($path, $callback, "VIEW");
        return $this;
    }

    public function exceptionCatch($callback)
    {
        if (!is_callable($callback))
            throw new Exceptions\Router("ExceptionCatch expects a callable function");

        $this->exception_callback = $callback;
        return true;
    }
    public function error404($callback)
    { //executes when no route is specified
        if ($this->shouldFallBack()) { //check if the current request doesn't match any request
            //            print_r($this->assigned_paths);
            $c = $callback($this->request, $this->response_instance); //call the callback, pass the params generated to it to be used
            if ($c instanceof Response) { //Check if return value from callback is a Response Object

                $this->writeResponse($c);
            } elseif ($c and !$c instanceof Response) {
                throw new Exceptions\Router("Expecting an instance of Response to be returned at \"Fallback\"");
            }
        }
    }
}
