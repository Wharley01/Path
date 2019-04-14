<?php



use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;
use Path\Core\Misc\Validator;



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
    return $response->json(['error' => "Error 404", 'params' => $request->server->REQUEST_URI], 404)->addHeader([
        "Access-Control-Allow-Origin" => "*"
    ]);
});
