<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/28/2018
 * Time: 5:10 AM
 */

namespace Path\Core\Router\Route;

use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Error\Exceptions;

abstract class Controller
{

    protected  $request, $response;
    /**
     * @param Request $request
     * @param Response $response
     * @return mixed|null
     * @throws Exceptions\Router
     */
    final public function response(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $controller_name = get_class($this);
        $_method_name = strtolower($request->METHOD);
        $_method_name[0] = strtoupper($_method_name[0]);

        $_call = ('on' . $_method_name);

        if (!method_exists($this, $_call))
            throw new Exceptions\Router("Request Method does not exist");

        $_response = $this->$_call($request, $response);

        if ($_response === false)
            throw new Exceptions\Router("Override \"$_call(){}\" method in  {$controller_name} to handle {$request->METHOD} Request");

        return $_response;
    }

    public function onDelete(Request $request, Response $response)
    {
        return false;
    }

    public function onPost(Request $request, Response $response)
    {
        return false;
    }

    public function onGet(Request $request, Response $response)
    {
        return false;
    }

    public function onPatch(Request $request, Response $response)
    {
        return false;
    }

    public function onRequest(Request $request, Response $response)
    {
        return false;
    }

    public function onPut(Request $request, Response $response)
    {
        return false;
    }

    public function onOptions(Request $request, Response $response)
    {
        return false;
    }

    public function getResponseAsArray($method,Request $request){
        if(!method_exists($this,$method))
            throw new Exceptions\Router("{$method} does not exist");
        $instance = new $this($request);

        $response = $instance->{$method}($request,new Response());
        if($content = json_decode($response->content,true)){
            return $content;
        }else{
            return $response->content;
        }
    }
}
