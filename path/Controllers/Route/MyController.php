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
use Path\Database\Models\Test;
use Path\Storage\Sessions;

import("Path/Database/Models/Test");

class MyController implements Controller
{
    private $session;
    public function __construct()
    {
        $this->session = new Sessions();
    }
    public function fetchAll(Request $request,Response $response){
//     return a response here
        return $response->json(['this is response from MyController Controller']);
    }

}