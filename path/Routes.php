<?php


use Path\Http\MiddleWare\IsLoggedUser;
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


/*
 * Your Routes Can be here
 * Happy coding
*/


$router->error404(function (Request $request, Response $response) {
    return $response->json(['error' => "Error 404", 'params' => $request->server->REQUEST_URI],404)->addHeader([
        "Access-Control-Allow-Origin" => "*"
    ]);
});
