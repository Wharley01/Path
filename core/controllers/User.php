<?php

/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Controller;


use Path\Controller;
use Path\Http\Request;
use Path\Http\Response;
use Path\Database\Models;

load_class([
    "Database/Models/User",
    "Controller"
]);
class User implements Controller
{
    public  $_user;

    public function __construct()
    {
        $this->_user = (new Models\User());
    }
    public function fetchAll(Request $request,Response $response){
//     return a response here
    }

}