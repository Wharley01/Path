<?php

namespace Path\Http\MiddleWare;
use Connection\Mysql;
use Data\Database;
use Path\Controller\User;
use Path\Http\MiddleWare;
use Path\Http\Request;
use Path\Http\Response;

// load_class(['Connection','Database']);
load_class("User","controllers");

class Auth extends User implements MiddleWare
{
    public function __construct()
    {
        parent::__construct();
        return true;
    }
    public function Control(Request $request,Response $response){
        return $this->Auth($request,$response);
    }
    public function test(){

    }

}