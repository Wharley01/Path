<?php

$router->get(
["path"       => "/api/user/@user_name/@user_id:int",
"middleware" => Response::MiddleWare(
Path\Http\MiddleWare\Auth::class,
function ($request,$params){
return (new Response())->json(["error" => "User ID not 300 it is {$params->user_id}","code" => 600,"params" => $params],401);
})],
function ($params) use ($router){
return (new Response())->json([
"greet" => "Hello world new json API {$router->request->fetch('name')}"
])->addHeader([
"Access-Control-Allow-Origin" => "*"
]);
});


/** @var Router $router */
@$router->POST("/api/user/@user_name/@user_id:int",function ($params) use ($router){
return (new Response())->json(["msg" => "This is profile page {$router->request->fetch('name')}"])->addHeader([
"Access-Control-Allow-Origin" => "*"
]);
});

$router->GET(["path" => '/user/@user_id:int/'],function ($params){
return((new Response())->json((Array)$params,200));
});

$router->GET(["path" => '/'],function ($params){
return(new Response("This is home page"));
});

$router->Error404(function (){
return (new Response("Error 404",404))->addHeader([
"Access-Control-Allow-Origin" => "*"
]);
});