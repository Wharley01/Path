<?php



use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;

$router = new Router();

$router->error404(function (Request $request, Response $response) {
    return $response->error('Error 404', [], 404);
});

$router->get('/', function () {
    //   echo phpinfo()
    return ['Path' => 'API VERSION 1'];
});

$router->end();
