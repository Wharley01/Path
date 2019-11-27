<?php


use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;

$router = new Router();
$router->setBuildPath("/");

$router->get("/test",function ($_,Response $response){
    return $response->json(['HELLO WORLD']);
});

$router->any("*", function (Request $request,Response $response){

    $response->setTitle("Hello world! a server rendered Javascript in PHP");

    $response->setState("name","adewale");//Accessible in Current route View in your Javascript with $state global variable
    $response->setState("school","mahmud");

    return $response->SSR();
});
