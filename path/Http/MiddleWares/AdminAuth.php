<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 11/23/2018
 * Time: 12:48 PM
 */

namespace Path\Http\MiddleWare;


use Path\Http\MiddleWare;
use Path\Http\Request;
use Path\Http\Response;

class AdminAuth implements MiddleWare
{
    public function __construct()
    {
    }
    public function Control(Request $request, Response $response)
    {
        // TODO: Implement Control() method.
        return true;
    }

}