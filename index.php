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

    // Catches any error,(for example Invalid parameter from user(browser))
    $router->exceptionCatch(function (Request $request, Response $response, array $error) {
        // $error array contains error message and path where the error occurred
        return $response->json(["error" => $error['msg']]);
    });

    $router->group(["path" => "/api/@version:{[1-3]}/"], function (Router $router) {//path can use Regex too
        // A route group
        $router->get("search/@query", function (Request $req, Response $res) {//@query will be treated as variable and will be available in $req->params object as $req->params->query
            // this will listen to http://yourhost.com/api/@version:{[1-3]}/search/@query
            return $res->json((array)$req->params);
        });

        $router->get([
            "path" => "/user/profile/@user_id:int",
            // Middle ware to authenticate USer
            "middleware" => Response::MiddleWare(
                \Path\Http\MiddleWare\Auth::class,
                function (Request $request, Response $response) {
                // a call back function that wil be called if middle-ware returns a false
                    return \Path\Views::Render("includes/header");//return a json content with response code 401
                }
            )
        ], "User->Profile");//This route uses Controller Class "User" and access its method Profile(Profile method will receive both Request and Response)

        $router->post("/user/search/@user_name", "User->UploadPicture");
        // Method fires when no route from this group is matched
        $router->error404(function (Request $request, Response $response) {
            return $response->json(['error' => "Ops, error 404"], 404);
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





