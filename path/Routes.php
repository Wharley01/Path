<?php

use Path\App\Controllers\Route\HookService;
use Path\App\Controllers\Route\WebHook;
use Path\App\Controllers\Route\WhatsAppWebHook;
use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;

$router = new Router();
//$router->response->disableCORS('http://192.168.0.128:9001');
$router->response->disableCORS(config("PROJECT->UI_URI"));
$router->group("v1", function (Router $router){
    $router->post("/handle-incoming-message", WhatsAppWebHook::class);
});
$router->get("SSE/@controller/@action", 'Path\Plugins\SSEController\SSEServer->watch');
$router->error404(function (Request $request, Response $response) {
    return $response->error('Error 404', [], 404);
});
$router->graph('/path-graph');
$router->setBuildPath("/");

$router->get('/', function (Request $request,Response $response) {
    return $response->success('BuySafe.ng API v1');
});
$router->post("/seller/deauthorize", WebHook::class.'->deAuthorize');
$router->post('/payment-hook', HookService::class.'->raveHook');

$router->end();

