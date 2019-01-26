<?php
/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Controller\Route;


use Path\Controller;
use Path\Http\Request;
use Path\Http\Response;
use Path\Database\Models;

import("Path/Database/Models/Test");

class Test implements Controller
{
    public  $_test;

    public function __construct()
    {
    }
    public function fetchAll(Request $request,Response $response){
//     return a response here
        return $response->json(['this is response from Test Controller']);
    }

}