<?php

use Path\Response;
use Path\Router;

require_once "core/kernel.php";

load_class([
    "Router","Request","Response"
]);

$router = new Router(new \Path\Request());
try{
    $router->GET("/api/user/@user_name/@user_id:int",function ($params){
        $arr = [
          "greet" => "Hello world"
        ];



        return (new Response(json_encode($arr),200))->addHeader([
            "Content-Type" => "application/json; charset=UTF-8",
            "Access-Control-Allow-Origin" => "*"
        ]);
    });

    $router->POST("/api/user/@user_id:int/delete",function ($params) use ($router){
        print_r($router->request::POST());
      echo "POST request received";
    });
}catch (\Path\RouterException $e){
    echo $e->getMessage();
}






