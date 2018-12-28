<?php

use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;

require_once "core/kernel.php";

load_class(
    [
        "Http/Router",
        "Http/Response",
        "Views"
    ]
);


try {
    $router = new Router();
    $router->setBuildPath("/dist");

    // Catches any error,(for example Invalid parameter from user(browser))
    $router->exceptionCatch(function (Request $request, Response $response, array $error) {
        // $error array contains error message and path where the error occurred
        return $response->json(["error" => $error['msg']]);
    });

    $router->any([
        "path"          => "/",
        "middleware"    => Request::MiddleWare(
            \Path\Http\MiddleWare\isProd::class,
            function (Request $request,Response $response){
//                Development Mode
                return $response->json(['mode' => 'Development Mode']);
            }
        )
    ],function (Request $request,Response $response){
//        Production Mode
        return $response->html("index.html");
    });

    $router->group(["path" => "/api/@version/"], function (Router $router) {//path can use Regex too
        // A route group
        //probably for API
        $router->get("user",function(Request $request, Response $response){
            return $response->json(["hello world",$request->fetch("ID")],200);
        });
//fetch all services
        $router->get("services/fetch/all","Services->fetchAll");
//fetch all project type
        $router->get("project-types/fetch/all","ProjectTypes->fetchAll");

        $router->error404(function (Request $request, Response $response) {
            return $response->json(['error' => "Error 404", 'params' => $request->fetch("name")])->addHeader([
                "Access-Control-Allow-Origin" => "*"
            ]);
        });
    });



    $router->error404(function (Request $request, Response $response) {
        return $response->json(['error' => "Error 404", 'params' => $request->fetch("name")])->addHeader([
            "Access-Control-Allow-Origin" => "*"
        ]);
    });
} catch (\Path\RouterException $e) {
    echo "There was an error: " . $e->getMessage();
} catch (\Path\PathException $e) {
    echo "Path error: " . $e->getMessage() . " trace: <pre>" . $e->getTraceAsString() . "</pre>";
} catch (\Path\DatabaseException $e) {
    echo "Database error: " . $e->getMessage() . " trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
