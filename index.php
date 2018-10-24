<?php

use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;

require_once "core/kernel.php";

load_class(["Http/Router","Http/Request","Http/Response"]);
load_class(["User"],"controllers");

$router = new Router(new Request());

try{
    $router->GET(
        ["path"       => "/api/user/@user_name/@user_id:int",
         "middleware" => Response::MiddleWare(
        Path\Http\MiddleWare\Auth::class,
        (new Response())->json(["error" => "User ID not 300"],401))],function ($params) use ($router){
        return (new Response())->json([
            "greet" => "Hello world new json API"
        ])->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });


    /** @var Router $router */
    @$router->POST("/api/user/@user_name/@user_id:int",function ($params) use ($router){
        return (new Response("Hello world"))->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });

    $router->GET(["path" => '/'],function ($params){
       return(new Response("This is home page"));
    });

    $router->Fallback(function (){
        return (new Response("Error 404",404))->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });


}catch (\Path\RouterException $e){
    echo $e->getMessage();
}






