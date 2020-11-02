<?php

use Path\App\Controllers\Route\HookService;
use Path\App\Controllers\Route\WebHook;
use Path\App\Controllers\Route\WhatsAppWebHook;
use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;

$router = new Router();
//Enter your UI SERVERS Seperated by comma
$router->response->disableCORS("");

$router->get("SSE/@controller/@action", 'Path\Plugins\SSEController\SSEServer->watch');
$router->error404(function (Request $request, Response $response) {
    return $response->error('Error 404', [], 404);
});
$router->graph('/path-graph');
$router->setBuildPath("/");

$router->get('/', function (Request $request,Response $response) {
    return $response->success('PATH v2.0.3');
});

$router->end();

