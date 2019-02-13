<?php
/**
 * Created by PhpStorm.
 * User: surphwahn
 * Date: 2/13/19
 * Time: 1:14 AM
 */

namespace Path\Http;


use Path\RouterException;

abstract class ModelController implements ModelHttpInterface {

    protected  $request, $response;

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed|null
     * @throws RouterException
     */
    public  function view(Request $request, Response $response){

        $this->request = $request;
        $this->response = $response;

        $call = ('on'.strtoupper($this->request->METHOD));

        if (!method_exists($this, $call))
            throw new RouterException("Request Method does not exist");

        $r_response = $this->$call($request, $response);

        if($r_response === null)
            throw new RouterException("Request Method is not allowed");

        return $r_response;

    }

    public function onDELETE(Request $request, Response $response){
        return null;
    }

    public function onPOST(Request $request, Response $response){
        return null;
    }

    public function onGET(Request $request, Response $response){
        return null;
    }

    public function onPATCH(Request $request, Response $response){
        return null;
    }

    public function onREQUEST(Request $request, Response $response){
        return null;
    }

    public function onPUT(Request $request, Response $response){
        return null;
    }

    public function onOPTIONS(Request $request, Response $response){
        return null;
    }

}

interface ModelHttpInterface {

    function onGET(Request $request, Response $response);

    function onPOST(Request $request, Response $response);

    function onDELETE(Request $request, Response $response);

    function onPATCH(Request $request, Response $response);

    function onREQUEST(Request $request, Response $response);

    function onOPTIONS(Request $request, Response $response);

    function onPUT(Request $request, Response $response);

}
