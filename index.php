<?php
namespace Path\Http;
session_start();

use Path\Http\MiddleWare\isProd;
use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;
use Path\Misc\Validator;

require_once "core/kernel.php";
require_once "core/bootstrap.php";
require_once "core/classes/Http/Router-new.php";

/** Uncomment the below lines of code for usage and testing */
//RouterN::group("api/@version", function () {
//
//    RouterN::group("/admin",function (){
//        RouterN::get("/all", function (Request $request,Response $response){
//            return $response->json(["All Admin /fetch/all"]);
//        });
//    }, 1);
//
//    RouterN::get("/test", function (Request $request,Response $response){
//        return $response->text("Hello world");
//    });
//
//    RouterN::group("/user",function(){
//
//        RouterN::group("/admin",function (){
//
//            RouterN::get("/all", function (Request $request,Response $response){
//                return $response->json(["All Admin /fetch/all"]);
//            });
//
//            RouterN::group('/prince', function (){
//                RouterN::get('/some', function (Request $request, Response $response){
//                    return $response->text("heLLo dear! Dirty hacks everywhere");
//                });
//            }, 3);
//
//        }, 2);
//
//        RouterN::get("/fetch/all", function (Request $request,Response $response){
//            return $response->json(["Showing /fetch/all"]);
//        });
//
//        RouterN::get("/fetch/@user_id",function (Request $request,Response $response){
//
//            $validator = new Validator();
//
//            $validator->values($request->inputs)->validate([
//                "name" => [
//                    [
//                        "rule" => "min:10",
//                        "error_msg"   => "name must be more than 10 characters",//you can have custom error message
//                    ],
//                    "min:5",//or just like this,(error msg will be generated on your behalf )
//                    [
//                        "rule"  =>  "required",//you can Omit the "error_msg key, it generates one for you
//                    ],[
//                        "rule"  =>  "regex:[\\d*]",//you can match a regex
//                    ]
//                ],
//                "school" => "required"//you don't necessarily have to use multiple rules
//            ]);
//
//            if($validator->hasError()){
//                // do something if there was an error
//            }
//
//
//            return $response->json($validator->getErrors());//get all invalidity error based on your defined rules
//
//        });
//
//    }, 1);
//
//    RouterN::error404(function (Request $request, Response $response) {
//        return $response->json(['error' => "Error 404", 'params' => $request->fetch("name")])->addHeader([
//            "Access-Control-Allow-Origin" => "*"
//        ]);
//    });
//
//});

try {
    //$__routes = new Router();
    //$__routes->get("SSE/@controller/@action","SSE->watch");
    //require_once "path/Routes.php";

//    \Path\Http\RouterN::get('/hello', function (){
//        echo 'Hello';
//    });

}catch (Throwable $e) {
    echo "<pre>";
    echo "Path error: " . $e->getMessage() . " trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
