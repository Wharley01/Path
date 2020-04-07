<?php


use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;

$router = new Router();
$router->response->disableCORS();
$router->error404(function (Request $request, Response $response) {
    return $response->error('Error 404', [], 404);
});


$router->graph('/path-graph');
$router->setBuildPath("/");
$router->get('/', function (Request $request, Response $response) {
    //   echo phpinfo()

    return $response->success('PATH v2.0.3');
});

$router->end();
