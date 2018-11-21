<?php

use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;

require_once "core/kernel.php";

load_class(["Http/Router","Http/Response","Views"]);
//load_class(["User"],"controllers");


try{
    $router = new Router();

    $router->ExceptionCatch(function (Request $req,Response $res,$err){
     return $res->json(["Hello World"]);
    });

    $router->group(["path" => "/api/@version:{[1-3]}/"],function (Router $router) {
//        echo "group 1";
        $router->GET("search/@query", function (Request $req,Response $res){
            return $res->json((array) $req->params);
        });
        $router->GET(["path" => "/user/@user_name/@user_id:int","middleware" => Response::MiddleWare(\Path\Http\MiddleWare\Auth::class,function (Request $request,Response $response){
            return $response->json(['error' => 'Invalid user,Try new user ID'],401);
        })],"User->Find");

        $router->Error404(function (Request $request,Response $response){
            return $response->json(['error' => "Ops, error 404"],404);
        });

    });


    $router->group(["path" => "/api/@version:{[4-8]}/"],function (Router $router){
//        echo "group 2";
        $router->GET("/delete/user/@user_id/","User->Auth");

        $router->GET("/user/@user_name",function (Request $request,Response $response){
//           Do any Thing here
        });
////This is using Controller
        $router->POST("/search/@query","Songs->Find");
//
//
        $router->Error404(function (){
            return (new Response("Error 404 for group 2",404))->addHeader([
                "Access-Control-Allow-Origin" => "*"
            ]);
        });
    });
   $router->Error404(function (Request $request,Response $response){
        return $response->json(['error' => "Error 404",'params' => $request->fetch("name")])->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });
}catch (\Path\RouterException $e){
    echo "There was an error: ". $e->getMessage();
}






