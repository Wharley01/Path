<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 2/8/2019
 * @Time 11:31 PM
 * @Project macroware-vue
 */

namespace Path\Core\Http\Watcher;


use Path\Core\Http\Response;
use Path\Core\Storage\Sessions;
use Path\Core\Router\Live\Controller;
use Path\Core\Error\Exceptions;

class SSE  implements WatcherInterface
{
    private $watching;
    public $params;
    private $controller_name;
    private $controller_instance;
    private $session;
    private $controller_path = "Path/Controllers/Live/";
    private $controller_namespace = "Path\App\\Controllers\\Live\\";
    private $throw_exception = false;
    private $error;
    private $has_executed = [];
    private $has_changes = [];
    private $response = [];
    private $response_instance;
    private $message;

    public function __construct($controller_name, $watching, $params)
    {
        $this->watching = explode(",", $watching);
        $this->params = self::generateParams($params);
        $this->controller_name = $controller_name;
        $this->session = new Sessions();
        $this->response_instance = new Response();
        $this->controller_instance = $this->iniController();
    }

    private function iniController($message = null): ?Controller
    {
        if ($this->controller_name) {
            if (strpos($this->controller_name, "\\") === false)
                $live_controller = $this->controller_namespace . $this->controller_name;
            else
                $live_controller = $this->controller_name;

            $controller = new $live_controller(
                $this,
                $this->session
            );
            return $controller;
        }
        return null;
    }

    public function watch($message = null)
    {
        $controller = $this->iniController($message);
        $this->$message = $message;

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
    private function hasExecuted($method)
    {
        $_method = $this->method($method);
        return !!$this->session->get($_method);
    }
    private function executed($method)
    {
        $has_executed = $this->session->get($this->method("has_executed")) ?? [];
        $has_executed[$method] = "executed";
        $this->session->store($this->method("has_executed"), $has_executed);
        return true;
    }
    private  function shouldExecute($method, $value)
    {
        $_method = $this->method($method);
        $cached_value = $this->session->get($_method);

        return (is_null($cached_value) || $cached_value != $value) || !$this->hasExecuted($method);
    }
    private function getMethodValue($controller, $method, $message)
    {
        return $controller->{$method}(
            $this->response_instance,
            $this,
            $this->session,
            $message
        );
    }
    private function execute(array $watch_list, Controller $controller, ?String $message = null, ?Bool $force_execute = false)
    {

        $watchable_methods = $this->watching;
        //        var_dump($_SESSION);
        //        var_dump($watch_list);
        if (is_null($watchable_methods)) {
            //            watch all watchable
            foreach ($watch_list as $_method => $_value) {
                if ($this->shouldExecute($_method, $_value) or $force_execute) {
                    $this->has_changes[$_method] = true;
                    $this->executed($_method);
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
                        $this->executed($_method);
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
        $controller = $this->iniController($message);
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

    public function close($message = null){
        $controller = $this->iniController($message);
        $this->message = $message;
        $controller->onClose(
            $this,
            $this->session
        );
        $this->reset();
    }

    public function navigate($params, $message = null)
    {
        $this->params = $params;
        $controller = $this->iniController($message);
        $controller->onConnect(
            $this,
            $this->session
        );
        $watch_list = $this->getWatchable($controller);
        $watch_list = self::castToString($watch_list);
        $this->execute($watch_list, $controller, $message, true);
        $this->cache($watch_list);
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


    /**
     * @param $method
     * @return bool
     */
    private function hasChanges($method)
    {
        return (@$this->has_changes[$method] === true);
    }


    private function getPrevValue($method)
    {
        $_method = $this->method($method);
        $cached_value = $this->session->get($_method);
        return $cached_value;
    }
    private function cache($watch_list)
    {
        foreach ($watch_list as $method => $value) {
            $method = $this->method($method);
            $already_cached = $this->session->get("watcher___cached_methods");
            if (!$already_cached) {
                $this->session->store("watcher___cached_methods", [$method]);
            } else {
                array_push($already_cached, $method);
                $this->session->store("watcher___cached_methods", $already_cached);
            }
            if ($this->shouldCache($method, $value)) {
                $this->session->store($method, $value);
            }
        }
    }


    private function getWatchable(Controller $controller): ?array
    {
        $watch_lists = array_keys(get_object_vars($controller));
        $resp = [];
        foreach ($watch_lists as $property) {
            $resp[$property] = $controller->$property;
        }
        return $resp;
    }

    private  function shouldCache($method, $value)
    {
        $_value = $this->session->get($method);
        return is_null($_value) || $_value !== $value;
    }

    private function method($method)
    {
        return "watcher___" . $method;
    }

    private static function generateParams($params): array
    {
        preg_match("/\[?([^\[^\]]+)\]?/i", $params, $matches);
        if (!isset($matches[1])) {
            echo "no match";
            return [];
        }
        $res = [];
        $params = explode(",", $matches[1]);

        foreach ($params as $param) {
            preg_match("/([^=]+)=([^=]+)/i", $param, $val);
            if (isset($val[0])) {
                $key = $val[1];
                $val = $val[2];
                $res[$key] = $val;
            }
        }

        return $res;
    }

    private static function castToString($arr)
    {
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[$key] = is_array($value) ? json_encode($value) : $value;
        }
        return $ret;
    }

    private function throwException($error_text)
    {
        if ($this->throw_exception) {
            throw new Exceptions\Watcher($error_text);
        } else {
            $this->error = $error_text;
        }
    }

    public function reset()
    {
        $cached_values = $this->session->get("watcher___cached_methods") ?? [];
        //        var_dump($cached_values);
        foreach ($cached_values as $method) {
            $this->session->delete($method);
        }
        $this->session->delete("watcher___cached_methods");
    }

    public function getMessage():?String
    {
        return $this->message;
    }

    public function getParams($key = null)
    {
        return $this->params[$key] ?? $this->params;
    }
}
