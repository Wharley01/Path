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

    $response->setHead(
        Response::HTMLTag('link',[
            "rel" => "stylesheet",
            "type" => "text/css",
            "href" => "/dist/css/server.css"
        ]),
        Response::HTMLTag('meta',[
            "name" => "title",
            "content" => "This is the search engine title"
        ]),
        Response::HTMLTag('meta',[
            "name" => "description",
            "content" => "This is the description"
        ])
    );

    $response->setBottom(
        Response::HTMLTag('script',[
            "src" => "/dist/js/client.js"
        ])
    );

    $response->setState("name","adewale");//Accessible in Current route View in your Javascript with $state global variable
    $response->setState("school","mahmud");

    return $response->SSR('/dist/js/server.js',200);
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
