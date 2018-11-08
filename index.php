<?php

use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;

require_once "core/kernel.php";

load_class(["Http/Router","Http/Response"]);
//load_class(["User"],"controllers");


try{
    $router = new Router();

    $router->ExceptionCatch(function ($req,$err){
        return(new Response())->json($err,500);
    });

   $router->group(["path" => "/api/@api_version:[1-3]"],function (Router $router){

            $router->POST("/delete/user/@user_id/","User->Find");

            $router->GET("/user/@user_name",function (Request $req,Response $res){
//           Do any Thing here
            });
//This is using Controller
       $router->POST("/search/@query","User->Find");


       $router->Error404(function (){
           return (new Response("Error 404 for group",404))->addHeader([
               "Access-Control-Allow-Origin" => "*"
           ]);
       });
   });
   $router->Error404(function (){
        return (new Response("Error 404",404))->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });
}catch (\Path\RouterException $e){
    echo "There was an error: ". $e->getMessage();
}






