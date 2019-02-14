<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/28/2018
 * Time: 5:10 AM
 */

namespace Path;

use Path\Http\Request;
use Path\Http\Response;
use Path\RouterException;

abstract class Controller{

    protected  $request, $response;

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed|null
     * @throws RouterException
     */
    final public function response(Request $request, Response $response){
        $this->request = $request;$this->response = $response;

        $_method_name = strtolower($request->METHOD);
        $_method_name[0] = strtoupper($_method_name[0]);

        $_call = ('on'.$_method_name);

        if (!method_exists($this, $_call))
            throw new RouterException("Request Method does not exist");

        $_response = $this->$_call($request, $response);

        if($_response === 0)
            throw new RouterException("Request Method is not allowed");

        return $_response;

    }

    public function onDelete(Request $request, Response $response){
        return 0;
    }

    public function onPost(Request $request, Response $response){
        return 0;
    }

    public function onGet(Request $request, Response $response){
        return 0;
    }

    public function onPatch(Request $request, Response $response){
        return 0;
    }

    public function onRequest(Request $request, Response $response){
        return 0;
    }

    public function onPut(Request $request, Response $response){
        return 0;
    }

    public function onOptions(Request $request, Response $response){
        return 0;
    }

}
