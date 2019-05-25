<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/16/2019
 * @Time 1:45 PM
 * @Project Path
 */

namespace Path\Core\Http\Watcher;


use Path\Core\Http\Response;
use Path\Core\Storage\Caches;
use Path\Core\Router\Live\Controller as LiveController;
use Path\Core\Storage\Sessions;
use Path\Core\Error\Exceptions;


class WS implements WatcherInterface
{
    private $path;
    private $watcher_namespace = "Path\App\\Controllers\\Live\\";
    private $watchers_path = "Path/Controllers/Live/";
    private $cache = [];
    public  $socket_key = "";
    private $response = [];
    private $response_instance;
    public  $session;
    private $throw_exception = false;
    public  $error = null;
    public  $server;
    public  $params = null;
    public  $client;
    private $message;
    private $controller_data = [
        "root"                 => null,
        "watchable_methods"    => null,
        "params"               => null
    ];
    private $pending_message = [];
    public $has_changes = [];
    /*
     * This will be set to true at first execution
     * so watcher will execute at least once after every initiation
     * */
    public $has_executed = [];
    /*
     * Holds all controllers being watched
     * */
    private $controller;
    /*
     *
     * */
    /**
     * Watcher constructor.
     * @param string $path
     * @param $session_id
     */
    public function __construct(string $path, $session_id)
    {
        $this->path = trim($path);
        $this->response_instance = new Response();
        $this->session = new Sessions($session_id);
        $this->extractInfo();
    }

    private function getResources($payload): array
    {
        $split_load = explode("&", $payload);
        $all_params = [];
        $watchable_methods = [];
        foreach ($split_load as $load) {
            if (preg_match("/Params=\[(.+)\]/i", $load, $matches)) {
                $params = explode(",", $matches[1]);
                foreach ($params as $param) {
                    $param = explode("=", $param);
                    $all_params[$param[0]] = $param[1];
                }
            }

            if (preg_match("/Watch=\[(.+)\]/i", $load, $matches)) {
                $list = $matches[1];
                $list = explode(",", $list);
                $watchable_methods = $list;
            }
        }

        return [
            'params' => $all_params,
            'watchable_methods' => $watchable_methods
        ];
    }
    private function extractInfo()
    {
        $path = $this->path;
        $path = array_values(array_filter(explode("/", $path), function ($p) {
            return strlen(trim($p)) > 0;
        }));
        $payload = array_slice($path, 1);
        $path = trim($path[0]);
        $url_resources = $this->getResources($payload[0] ?? "");
        $this->controller_data['root']                 = $path;
        $this->controller_data['watchable_methods']    = $url_resources['watchable_methods'];
        $this->controller_data['params']               = $url_resources['params'];
        $this->params                                  = $url_resources['params'];
        $this->controller = $this->getController(null,true);
    }

    private static function isValidClassName($class_name)
    {
        return preg_match("/^[\w]+$/", $class_name);
    }
    public function getController($message = null,$new_instance = false): ?LiveController
    {

        if($new_instance){
            $path = $this->controller_data['root'];
            if (!self::isValidClassName($path)) {
                $this->throwException("Invalid LiveController Name \"{$path}\" ");
            }
            if ($path) {
                if (strpos($path, "\\") === false)
                    $path = $this->watcher_namespace . $path;

                $this->message  = $message;
                $controller = new $path(
                    $this,
                    $this->session
                );

                return $controller;
            }
        }
        return $this->controller;
    }

    public function getMessage():?String{
        return $this->message ?? null;
    }

    public function watch($message = null)
    {
        $controller = $this->getController($message);
        $this->message = $message;
        $controller->onConnect(
            $this,
            $this->session
        );
        $watchable_list = $this->getWatchable($controller);
        if (count(array_keys($watchable_list)) < 1) {
            $this->throwException("Specify at least 1 \"watchable\" as public properties in " . get_class($controller));
        }

        $watch_list = self::castToString($watchable_list);
        //        cache watch_list values if not already cached
        $this->execute($watch_list, $controller);
        $this->cache($watch_list);
    }

    private function getWatchable(LiveController $controller): ?array
    {
        $watch_lists = array_keys(get_object_vars($controller));
        $resp = [];
        foreach ($watch_lists as $property) {
            $resp[$property] = $controller->$property;
        }
        return $resp;
    }

    private static function castBoolToStr($value)
    {
        return $value;
    }
    private static function castToString($arr)
    {
        $ret = [];
        foreach ($arr as $key => $value) {
            $value = self::castBoolToStr($value);
            $ret[$key] = is_array($value) ? json_encode($value) : $value;
        }
        return $ret;
    }
    private static function shouldCache($method, $value)
    {
        $_value = Caches::get($method);
        return is_null($_value) || $_value !== $value;
    }

    private function method($method)
    {
        return md5($this->socket_key . $method);
    }

    private function cache($watch_list)
    {
        foreach ($watch_list as $method => $value) {
            $method = $this->method($method);
            if (self::shouldCache($method, $value)) {
                //                echo "caching {$method} to {$value}".PHP_EOL;
                //                var_dump($value);
                Caches::set($method, $value);
            }
        }
    }

    public function clearCache()
    {
        $controller = $this->controller;
        $watch_list = $this->getWatchable($controller);
        $watch_list = self::castToString($watch_list);

        foreach ($watch_list as $method => $value) {
            $method = $this->method($method);
            Caches::delete($method);
        }
    }

    private  function shouldExecute($method, $value)
    {
        $_method = $this->method($method);
        $cached_value = Caches::get($_method);
        return (is_null($cached_value) || $cached_value != $value) || !@$this->has_executed[$method];
    }

    private function getPrevValue($method)
    {
        $_method = $this->method($method);
        $cached_value = Caches::get($_method);
        return $cached_value;
    }
    private function getMethodValue($controller, $method, $message)
    {
        $this->message = $message;
        return $controller->{$method}(
            $this->response_instance,
            $this,
            $this->session
        );
    }
    public function execute($watch_list, $controller, $message = null, $force_execute = false)
    {
        $watchable_methods = $this->controller_data['watchable_methods'];
        //        var_dump($_SESSION);
        //        var_dump($watch_list);
        if (is_null($watchable_methods)) {
            //            watch all watchable
            foreach ($watch_list as $_method => $_value) {
                if ($this->shouldExecute($_method, $_value) or $force_execute) {
                    $this->has_changes[$_method] = true;
                    $this->has_executed[$_method] = true;
                    if (!method_exists($controller, $_method)) {
                        $this->response[$_method][] = $_value;
                        $this->response[$_method][] = $this->getPrevValue($_method);
                    } else {

                        $response = $this->getMethodValue($controller, $_method, $message);
                        $this->response[$_method][] = $response;
                        $this->response[$_method][] = $this->getPrevValue($_method);
                    }
                } else {
                    $this->has_changes[$_method] = false;
                }
            }
        } else {
            //            validate the watchlist
            foreach ($watchable_methods as $method) {
                $_method = $method;
                if (isset($watch_list[$_method])) {
                    $_value = @$watch_list[$_method];
                    if ($this->shouldExecute($_method, $_value) or $force_execute) {
                        $this->has_changes[$_method] = true;
                        $this->has_executed[$_method] = true;
                        if (!method_exists($controller, $_method)) {
                            $this->response[$_method][] = $_value;
                            $this->response[$_method][] = $this->getPrevValue($_method);
                        } else {
                            $response = $this->getMethodValue($controller, $_method, $message);
                            $this->response[$_method][] = $response;
                            $this->response[$_method][] = $this->getPrevValue($_method);
                        }
                    } else {
                        $this->has_changes[$_method] = false;
                    }
                }
            }
        }
    }
    public function receiveMessage($message)
    {
        $controller = $this->getController($message);
        $this->message = $message;
        $controller->onMessage(
            $this,
            $this->session
        );
        $watch_list = $this->getWatchable($controller);
        $watch_list = self::castToString($watch_list);
        $this->execute($watch_list, $controller, $message);
        $this->cache($watch_list);
    }

    public function sendMessage($message, $to = null)
    {
        $this->pending_message[] = [
            'message' => $message,
            'to'      => $to
        ];
    }
    public function navigate($params, $message = null)
    {
        $this->controller_data['params'] = $params;
        $controller = $this->getController($message);
        $this->message = $message;
        $controller->onConnect(
            $this,
            $this->session
        );
        $watch_list = $this->getWatchable($controller);
        $watch_list = self::castToString($watch_list);
        $this->execute($watch_list, $controller, $message, true);
        $this->cache($watch_list);
    }

    public function close($message = null){
        $controller = $this->getController($message);
        $this->message = $message;
        $controller->onClose(
            $this,
            $this->session
        );
        $this->clearCache();
    }

    private function throwException($error_text)
    {
        if ($this->throw_exception) {
            throw new Exceptions\Watcher($error_text);
        } else {
            $this->error = $error_text;
        }
    }
    public function getResponse()
    {
        $response = [];
        foreach ($this->response as $key => $values) {
            //              check if value is an instance of response, then set to appropriate data type
            if ($this->hasChanges($key)) {

                foreach ($values as $value) {
                    if ($value instanceof Response) {
                        $response[$key][] = [
                            "data"           => $value->content,
                            "status"         => $value->status,
                            "headers"        => $value->headers,
                        ];
                    } else {
                        $response[$key][] = [
                            "data"           => $value,
                            "status"         => 200,
                            "headers"        => []
                        ];
                    }
                }
            }
        }
        $this->response = [];
        return $response;
    }
    public static function log($text)
    {
        echo PHP_EOL . $text . PHP_EOL;
    }

    /**
     * @return bool
     */
    public function changesOccurred():bool
    {
        foreach ($this->has_changes as $method => $status) {
            if ($status === true) {
                return true;
            }
        }
        return false;
    }

    public function hasPendingMessage(): bool
    {
        return count($this->pending_message) > 0;
    }

    public function getPendingMessage()
    {
        $message = array_shift($this->pending_message);
        $msg = $message['message'];
        $to = $message['to'];
        $response = [];

        if ($msg instanceof Response) {
            if (!is_null($to)) {
                $response[$to][] = [
                    "data"           => $msg->content,
                    "status"         => $msg->status,
                    "headers"        => $msg->headers,
                ];
                $response[$to][] = [
                    "data"           => $msg->content,
                    "status"         => $msg->status,
                    "headers"        => $msg->headers,
                ];
            } else {
                $response = [
                    "data"           => $msg->content,
                    "status"         => $msg->status,
                    "headers"        => $msg->headers,
                ];
            }
        } else {
            if (!is_null($to)) {
                $response[$to][] = [
                    "data"           => $msg,
                    "status"         => 200,
                    "headers"        => [],
                ];
                $response[$to][] = [
                    "data"           => $msg,
                    "status"         => 200,
                    "headers"        => [],
                ];
            } else {
                $response = [
                    "data"           => $msg,
                    "status"         => 200,
                    "headers"        => [],
                ];
            }
        }
        return json_encode($response);
    }


    /**
     * @param $method
     * @return bool
     */
    private function hasChanges($method)
    {
        return (@$this->has_changes[$method] === true);
    }

    public function getParams($key = null){
        return $this->params[$key] ?? $this->params;
    }
}
