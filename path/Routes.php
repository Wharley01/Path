<?php


use Path\Http\MiddleWare\isProd;
use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;
use Path\Misc\Validator;

$router = new Router();
$router->setBuildPath("/");

// Catches any error,(for example Invalid parameter from user(browser))
$router->exceptionCatch(function (Request $request, Response $response, array $error) {
    // $error array contains error message and path where the error occurred
    return $response->json(["error" => $error['msg']]);
});

$router->any([
    "path"          => ['/','/home','/testing'],
    "middleware"    => isProd::class
],"Test->fetchAll");

$router->get("watcher-test",function (Request $request,Response $response){
   return $response->html("websocket.html");
});

$router->group(["path" => "api/@version/"], function (Router $router) {//path can use Regex too
    // A route group
    //probably for API
    $router->get("/test",function (Request $request,Response $response){
        return $response->text("Hello world");
    });
    $router->group("user",function(Router $router){
        //fetch all services
        $router->get("fetch/all",function (Request $request,Response $response){
            return $response->json(["Showing /fetch/all"]);
        });
        $router->get("fetch/@user_id",function (Request $request,Response $response){

            $validator = new Validator();

            $validator->values($request->inputs)->validate([
                "name" => [
                    [
                        "rule" => "min:10",
                        "error_msg"   => "name must be more than 10 characters",//you can have custom error message
                    ],
                    "min:5",//or just like this,(error msg will be generated on your behalf )
                    [
                        "rule"  =>  "required",//you can Omit the "error_msg key, it generates one for you
                    ],[
                        "rule"  =>  "regex:[\\d*]",//you can match a regex
                    ]
                ],
                "school" => "required"//you don't necessarily have to use multiple rules
            ]);

            if($validator->hasError()){
                // do something if there was an error
            }


            return $response->json($validator->getErrors());//get all invalidity error based on your defined rules

        });

    });

    $router->group("admin",function (Router $router){
        $router->group("view/",function (Router $router){
            echo "sub-sub-sub- group";
            $router->get("users/","User->testError");
        });
    });


//        $router->error404(function (Request $request, Response $response) {
//            return $response->json(['error' => "Error 404", 'params' => $request->fetch("name")])->addHeader([
//                "Access-Control-Allow-Origin" => "*"
//            ]);
//        });
});



$router->error404(function (Request $request, Response $response) {
    return $response->json(['error' => "Error 404", 'params' => $request->server->REQUEST_URI])->addHeader([
        "Access-Control-Allow-Origin" => "*"
    ]);
});
