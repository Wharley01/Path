<?php

namespace Path\Http\MiddleWare;
use Connection\Mysql;
use Data\Database;
use Path\Controller\User;
use Path\Http\MiddleWare;
load_class(['Connection','Database']);
load_class("User","controllers");

class Auth extends User implements MiddleWare
{
    public function __construct()
    {
        parent::__construct(new Database(new Mysql()));
        return true;
    }
    public function Control($request,$params){
        return $this->Auth($params);
    }
    public function test(){

    }

}