<?php

namespace Path\Http\MiddleWare;
use Path\Controller;
use Path\Http\MiddleWare;

load_class("User","controllers");

class Auth extends MiddleWare
{
    public function __construct()
    {
        return true;
    }
    public function Control($request,$params){
        return $params->user_id == 300;
    }

}