<?php
/**
 * @Author Sulaiman.Adewale
 * @File Router
 * @Date: 10/22/2018
 * @Time: 12:14 AM
 */

namespace Path\Core\Http;

use Path\Core\Controller;
use Path\Core\Database\Connections\MySql;
use Path\Core\Http\Request;
use Path\Core\RouterException;
use Path\Core\Utilities;

import(
    "core/classes/Http/MiddleWare",
    "core/classes/Utilities",
    "core/classes/Http/Request",
    "core/classes/Route/Static/Controller",
    "core/classes/Database/Model",
    "core/classes/Database/Connections/MySql"
);

class RouterOld
{
    public static $request;
    public  $root_path = "";
    private $database;
    private static $response_instance;
    private static $build_path = "";
    private $controllers_path = "path/Controllers/Route/";
    private $controllers_namespace = "path\Controller\Route\\";
    private $middleware_path = "path/Http/MiddleWares/";
    private $middleware_namespace = "Path\Http\MiddleWare\\";
    /*
      *
      * [//to hold all paths assigned
      *  "root/path" => "path/"
      *  ];
      *
     * */
    public static $route_tree = [ //to hold all paths assigned
    ];
    private static $exception_callback; //Callback exception

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
    public static $real_path;

    /**
     * Router constructor.
     * @param string $root_path
     * @param string $path
     * @param null $callback
     * @param string $method
     * @param bool $is_group
     */
    public function __construct($root_path, $path = "", $callback = null, $method = "GET", $is_group = false)
    {
        //        if($root_path){
        $this->root_path = $root_path;
        //        }
        self::$request = new Request();
        self::$real_path =  preg_replace(
            "/[^\w:\/\d\-].*$/m",
            "",
            self::$request->server->REQUEST_URI
                ??
                self::$request->server->REDIRECT_URL
        );
        if (strlen(trim($path)) > 0) {
            self::$response_instance = new Response(self::$build_path);
            if (!$is_group)
                $this->processMultipleRequestPath($path, $callback, "GET");
            else
                $this->handleGroupRequest($path, $callback);
        }
    }

    public static function setBuildPath($path)
    {
        self::$build_path = $path;
        self::$response_instance = new Response(self::$build_path);
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
    private static function path_matches($param, $raw_param)
    {
        //        echo $param,$raw_param;
        $raw_param = substr($raw_param, 1);
        if (strpos($raw_param, ":") > -1) {
            $type = strtolower(explode(":", $raw_param)[1]);
            switch ($type) {
                case "int":
                    if (!preg_match("/^\d+$/", $param)) {
                        return false;
                    }
                    break;
                case "float":
                    if (!preg_match("/^\d+\.\d+$/", $param)) {
                        return false;
                    }
                    break;
                default:
                    $type = preg_replace("/\}$/", "", preg_replace("/^\{/", "", $type));
                    if (!preg_match("/{$type}/", $param)) { //Check if the regex match the URL parameter
                        return false;
                    }

                    break;
            }
        }
        return true;
    }
    private static function is_root($real_path, $path)
    {
        $b_real_path = array_values(array_filter(explode("/", $real_path), function ($p) {
            return strlen(trim($p)) > 0 && trim($p[0]) != "?";
        })); //get all paths in a array, filter
        $b_path = array_values(array_filter(explode("/", $path), function ($p) {
            return strlen(trim($p)) > 0;
        }));
        //        echo "Comparing: ".$path.PHP_EOL;
        //        var_dump([
        //            "b_real_path" => $b_real_path,
        //            "b_path" => $b_path,
        //        ]);
        //        echo PHP_EOL.PHP_EOL.PHP_EOL;
        if ($real_path == $path)
            return true;

        $matches = 0;
        for ($i = 0; $i < count($b_path); $i++) { //loop through the path template instead of real  path

            $c_path = trim(@$b_path[$i]);
            $c_real_path = trim(@$b_real_path[$i]);
            //            echo $c_real_path,"/",$c_path;
            if ($c_path == $c_real_path) { //if current templ path == browser path, count as matched
                $matches++;
            } elseif ($c_path != $c_real_path && $c_path[0] == "@" && self::path_matches($c_real_path, $c_path)) { //if current path templ not equal to current raw path, and current path templ is is param, and the param obeys the restriction add to match count
                $matches++;
            }
        }
        //        echo PHP_EOL."it was:";
        //        var_dump($matches == count($b_path));
        //        echo var_dump($matches == count($b_path));
        return $matches == count($b_path);
    }
    /**
     * Compare browser path and path template to
     * @param $real_path
     * @param $path
     * @return bool
     */
    public static function compare_path($real_path, $path)
    {
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

        if ($real_path == $path) { //if the path are literally the same, don't do much hard job, return true
            return true;
        }
        //        Else, continue checking

        $matched = 0; //number of matched paths(Both template and path
        for ($i = 0; $i < count($b_real_path); $i++) {
            if ($i > count($b_path)) { //if the amount of url path is more than required, return false
                return false;
            }
            //            Continue execution
            $c_path = @$b_path[$i]; //current path (template)
            $c_real_path = @$b_real_path[$i]; //current path (from web browser)
            if ($c_path == $c_real_path) { // current template path is equal to real path from browser count it as matched
                $matched++; //count
            } elseif ($c_path != $c_real_path && $c_path[0] == "@" && !!$c_path && $c_real_path) { //if path template is not equal to current path, and real path
                $matched++;
            } else {
                return false;
            }
        }
        return $matched == count($b_path);
    }

    private static function write_response($response)
    {
        if (!$response instanceof Response)
            throw new RouterException("Callback function expected to return an instance of Response Class");
        http_response_code($response->status); //set response code
        self::set_header($response->headers); //set header
        print($response->content);
        @ob_flush();
        @flush();
        die();
    }

    /**
     * @param $param
     * @param $raw_param
     * @param $path
     * @return bool
     * @throws RouterException
     */
    public function type_check($param, $raw_param, $path)
    { //throw exception if specified type doesn't match the dynamic url(url from the browser)
        if (strpos($raw_param, ":") > -1) {
            $type = strtolower(explode(":", $raw_param)[1]);
            switch ($type) {
                case "int":
                    if (!preg_match("/^\d+$/", $param)) {
                        $error = ["msg" => "{$param} is not a {$type} in {$path}", "path" => $path];
                        if (is_callable(self::$exception_callback)) {
                            $exception_callback = call_user_func_array(self::$exception_callback, [self::$request, self::$response_instance, $error]);
                        } else {
                            $exception_callback = false;
                        }
                        if ($exception_callback) {
                            self::write_response($exception_callback);
                            return false;
                        } else {
                            throw new RouterException($error['msg']);
                        }
                    }
                    break;
                case "float":
                    if (!preg_match("/^\d+\.\d+$/", $param)) {
                        $error = ["msg" => "{$param} is not a {$type} in {$path}", "path" => $path];
                        if (is_callable(self::$exception_callback)) {
                            $exception_callback = call_user_func_array(self::$exception_callback, [self::$request, self::$response_instance, $error]);
                        } else {
                            $exception_callback = false;
                        }
                        if ($exception_callback) {
                            self::write_response($exception_callback);
                            return false;
                        } else {
                            throw new RouterException($error['msg']);
                        }
                    }

                    break;
                default:
                    $type = preg_replace("/\}$/", "", preg_replace("/^\{/", "", $type));
                    if (!preg_match("/{$type}/", $param)) { //Check if the regex match the URL parameter
                        $error = ["msg" => "{$param} does not match {$type} Regex in {$path}", "path" => $path];
                        if (is_callable(self::$exception_callback)) {
                            $exception_callback = call_user_func_array(self::$exception_callback, [self::$request, self::$response_instance, $error]);
                        } else {
                            $exception_callback = false;
                        }
                        if ($exception_callback) {
                            self::write_response($exception_callback);
                            return false;
                        } else {
                            throw new RouterException($error['msg']);
                        }
                    }


                    break;
            }
        }
        return true;
    }
    private function concat_path($root, $path)
    {
        return $root . $path;
    }
    public static function get_param_name($raw_param)
    {
        $param = explode(":", $raw_param);
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
    ) {
        $path_str = $path;
        $b_real_path = array_values(array_filter(explode("/", $real_path), function ($p) {
            return strlen(trim($p)) > 0;
        }));
        $b_path = array_values(array_filter(explode("/", $path), function ($p) {
            return strlen(trim($p)) > 0;
        }));;

        $params = [];


        for ($i = 0; $i < count($b_real_path); $i++) {
            if ($i >= count($b_path)) { //if the amount of url path is more than required, return false
                return (object)$params;
            }
            $path = $b_path[$i];
            if (!is_null($b_path[$i]) && @$path[0] == "@" && !is_null($b_real_path[$i])) {
                //                TODO: check for string typing
                $raw_param = $path;
                $param = self::get_param_name(substr($path, 1, strlen($path)));
                if (!self::path_matches($b_real_path[$i], $raw_param)) { //if type check doesn't match don't return any param
                    return false;
                }
                $params[$param] = $b_real_path[$i];
            }
        }
        return (object)$params;
    }

    private function validateAllMiddleWare($middle_wares, $params, $_path, $real_path)
    {
        if (!is_array($middle_wares) || is_string($middle_wares))
            $middle_wares = [$middle_wares];

        $request = new Request();
        $request->params = $params;
        foreach ($middle_wares as $middle_ware) {
            //            echo $middle_ware;
            if ($middle_ware) {
                $middle_ware_name = explode("\\", $middle_ware);
                $middle_ware_name = $middle_ware_name[count($middle_ware_name) - 1];
                //            Load middleware class
                import("{$this->middleware_path}{$middle_ware_name}");
                $ini_middleware = new $middle_ware();

                if ($ini_middleware instanceof MiddleWare) {
                    //            initialize middleware
                    //                call the fall_back response
                    $fallback_response = $ini_middleware->fallBack($request, self::$response_instance);


                    //            Check middle ware return
                    $check_middle_ware = $ini_middleware->validate($request, self::$response_instance);
                    if (!$check_middle_ware) { //if the middle ware control method returns false

                        if (!is_null($fallback_response)) { //if user has a fallback method

                            if ($fallback_response && !$fallback_response instanceof Response) {
                                throw new RouterException(" \"fallBack\" method for \"{$middle_ware_name}\" MiddleWare is expected to return an instance of Response");
                            } else {
                                self::write_response($fallback_response);
                                return true;
                            }
                        }

                        if (self::$exception_callback) {
                            $exception_callback = call_user_func_array(self::$exception_callback, [$request, self::$response_instance, ['error_msg' => "MiddleWare validation failed for \"{$real_path}\""]]);
                            if ($exception_callback instanceof Response) {
                                self::write_response($exception_callback);
                            }
                        } else {
                            throw new RouterException("MiddleWare validation failed for \"{$real_path}\"");
                        }
                    }
                } else {
                    throw new RouterException("Expected \"{$middle_ware->method}\" to implement \"Path\\Http\\MiddleWare\" interface in \"{$_path}\"");
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
     * @throws RouterException
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
            throw new RouterException("Specify Path for your router");

        $_path = $this::joinPath($root, $path);
        $real_path = trim(self::$real_path);
        $params = $this->get_params($real_path, $_path);
        if (!self::compare_path($real_path, $_path) && !$is_group) { //if the browser path doesn't match specified path template
            return false;
        }


        static::$route_tree[$root][] = [
            "path"      => $_path,
            "method"    => $method,
            "is_group"  => $is_group
        ];
        //        var_dump(static::$route_tree);

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
            if (is_string($callback)) {
                $_callback = $this->breakController($callback, $params);

                try {
                    $class = $_callback->ini_class->{$_callback->method}($request, self::$response_instance);
                } catch (\Throwable $e) {
                    throw new RouterException($e->getMessage() . PHP_EOL . "<pre>" . $e->getTraceAsString() . "</pre>");
                }
                if ($class instanceof Response) { //Check if return value from callback is a Response Object
                    self::write_response($class);
                }
            } else {
                /** ************************************************ */
                //Check if the callback is a Controller instance and call the response method with the $request and $response as parameter

                if ($callback instanceof \Closure)
                    $c = $callback($request, self::$response_instance);
                elseif ($callback instanceof Controller or method_exists($callback, 'response'))
                    $c = $callback->response($request, self::$response_instance);
                else
                    throw new RouterException('Custom Class Object must have a response method');

                //                $c = ! $callback instanceof \Closure ? $callback->response($request, self::$response_instance)
                //                    : $callback($request,self::$response_instance);

                if ($c instanceof Response) { //Check if return value from callback is a Response Object
                    self::write_response($c);
                } elseif ($c and !$c instanceof Response) {
                    throw new RouterException("Expecting an instance of Response to be returned at \"GET\" -> \"$_path\"");
                }
            }
            //call the callback, pass the params generated to it to be used
        }
    }

    /**
     * @return bool
     */
    private static function should_fall_back()
    {
        $real_path = trim(self::$real_path);
        $current_method = strtoupper(self::$request->METHOD);

        foreach (static::$route_tree as $root => $paths) {
            $root = $root == "/" ? "" : $root;
            for ($i = 0; $i < count(static::$route_tree); $i++) {
                //                var_dump($root.$paths[$i]['path']);
                if ($paths[$i]['is_group'] and self::is_root($real_path, $paths[$i]['path'])) {
                    return false;
                }
                if (self::compare_path($real_path, $root . $paths[$i]['path']) && ($paths[$i]['method'] == $current_method || $paths[$i]['method'] == "ANY")) {
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

    public static function group($path, $callback)
    {
        return new static(null, $path, $callback, "ANY", true);
    }
    private function breakController($controller_str, $params)
    {
        //        break string
        if (!preg_match("/([\S]+)\-\>([\S]+)/", $controller_str))
            throw new RouterException("Invalid Router String");

        //        Break all string to array
        $contr_breakdown = array_values(array_filter(explode("->", $controller_str), function ($m) {
            return strlen(trim($m)) > 0;
        })); //filter empty array
        $class_ini = $contr_breakdown[0];
        import(
            "core/classes/Route/Static/Controller",
            $this->controllers_path . $class_ini //load dynamic controller
        );
        //        load_class($class_ini,"controllers");
        $class_ini = $this->controllers_namespace . $class_ini;
        try {
            $request = new Request();
            $request->params = $params;
            $class_ini = new $class_ini($request, self::$response_instance);
        } catch (\Throwable $e) {
            throw new RouterException($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }

        return (object)["ini_class" => $class_ini, "method" => $contr_breakdown[1]];
    }

    private function handleGroupRequest($path, $callback)
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

        $real_path = trim(self::$real_path);
        if (self::is_root($real_path, self::joinPath($this->root_path, $_path))) {
            $this->response("ANY", $this->root_path, $_path, $callback, $_middle_ware, $_fallback, true);
        }
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

        foreach ($_path as $each_path) {
            $this->processRequest(["path" => trim($each_path), "middleware" => $_middle_ware, "fallback" => $_fallback], $callback, $method);
        }
    }
    private function processRequest($path, $callback, $method)
    {
        //        echo "<pre>";
        //        var_dump($path);
        if (is_array($path)) { //check if path is associative array or a string
            $_path = $path['path'] ?? null;
            $_middle_ware = $path['middleware'] ?? null;
            $_fallback = $path['fallback'] ?? null;
        } else {
            $_path = $path;
            $_middle_ware = null;
            $_fallback    = null;
        }
        $real_path = trim(self::$real_path);
        //Check if path is the one actively visited in browser
        if ((strtoupper(self::$request->METHOD) == $method || $method == "ANY") && self::compare_path($real_path, self::joinPath($this->root_path, $_path))) {
            //            Check if $callback is a string, parse appropriate
            $this->response($method, $this->root_path, $_path, $callback, $_middle_ware, $_fallback, false);
        }
    }

    /**
     * @param $path
     * @param $callback
     * @return $this
     * @throws RouterException
     */
    public static function get($path, $callback)
    {
        //   this is the bottle neck, not being able to use the current root
        return new static(self::$root_path, $path, $callback, "GET");
    }
    public static function any($path, $callback)
    {
        return new static(null, $path, $callback, "ANY");
    }
    public static function post($path, $callback)
    {
        return new static(null, $path, $callback, "POST");
    }
    public static function put($path, $callback)
    {
        return new static(null, $path, $callback, "PUT");
    }
    public static function patch($path, $callback)
    {
        return new static(null, $path, $callback, "GET");
    }
    public static function delete($path, $callback)
    {
        return new static(null, $path, $callback, "DELETE");
    }
    public static function copy($path, $callback)
    {
        return new static(null, $path, $callback, "COPY");
    }
    public static function head($path, $callback)
    {
        return new static(null, $path, $callback, "HEAD");
    }
    public static function options($path, $callback)
    {
        return new static(null, $path, $callback, "OPTIONS");
    }
    public static function link($path, $callback)
    {
        return new static(null, $path, $callback, "LINK");
    }
    public static function unlink($path, $callback)
    {
        return new static(null, $path, $callback, "UNLINK");
    }
    public static function purge($path, $callback)
    {
        return new static(null, $path, $callback, "PURGE");
    }
    public static function lock($path, $callback)
    {
        return new static(null, $path, $callback, "LOCK");
    }
    public static function propFind($path, $callback)
    {
        return new static(null, $path, $callback, "PROPFIND");
    }
    public static function view($path, $callback)
    {
        return new static(null, $path, $callback, "VIEW");
    }

    public static function exceptionCatch($callback)
    {
        if (!is_callable($callback))
            throw new RouterException("ExceptionCatch expects a callable function");

        self::$exception_callback = $callback;
        return true;
    }
    public static function error404($callback)
    { //executes when no route is specified
        if (self::should_fall_back()) { //check if the current request doesn't match any request
            //            print_r($this->assigned_paths);
            $c = $callback(self::$request, self::$response_instance); //call the callback, pass the params generated to it to be used
            if ($c instanceof Response) { //Check if return value from callback is a Response Object

                self::write_response($c);
            } elseif ($c and !$c instanceof Response) {
                throw new RouterException("Expecting an instance of Response to be returned at \"Fallback\"");
            }
        }
    }
}
