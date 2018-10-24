<?php

use Path\Http\Response;
use Path\Http\Router;

require_once "core/kernel.php";

load_class(["Http/Router","Http/Request","Http/Response"]);
load_class(["User"],"controllers");

$router = new Router(new \Path\Http\Request());

try{
    $router->GET(
        ["path"       => "/api/user/@user_name/@user_id:int",
         "middleware" => Response::MiddleWare(
        Path\Http\MiddleWare\Auth::class,
        (new Response())->json(["error" => "User ID not 300"],400)->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ])
        )],function ($params) use ($router){
        $arr = [
          "greet" => "Hello world new json API"
        ];

        return (new Response())->json($arr)->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });


    /** @var Router $router */
    @$router->POST("/api/user/@user_name/@user_id:int",function ($params) use ($router){
        return (new Response("Hello world"))->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });

    $router->Fallback(function (){
        return (new Response("Error 404",200))->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });


}catch (\Path\RouterException $e){
    echo $e->getMessage();
}






