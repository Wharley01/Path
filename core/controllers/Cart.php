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
    "Database/Models/Cart",
    "Controller"
]);
class Cart implements Controller
{
    public  $_cart;

    public function __construct()
    {
        $this->_cart = (new Models\Cart());
    }
    public function fetchAll(Request $request,Response $response){
//     return a response here
    }

}