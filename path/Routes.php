<?php


use Path\App\Mail\Mailables;
use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;
use Path\Core\Misc\Validator;
use Path\Core\Mail;


$router = new Router();
$router->setBuildPath("/");

// Catches any error,(for example Invalid parameter from user(browser))
$router->exceptionCatch(function (Request $request, Response $response, array $error) {
    // $error array contains error message and path where the error occurred
    return $response->json(["error" => $error['msg']]);
});
$router->any("/", function (Request $request,Response $response){
    return $response->bindState([
        "name" => "Adewale"
    ])->html("/test.html");
});

$router->get("test/@command", function () {


    $mailer = new Mail\Sender(Mailables\TestMail::class);
    $mailer->bindState([
        "name" => "Testing Testing",
        "test" => "Testing",
        "new_email" => "Another@email.com"
    ]);
    // echo "hello world";
    $mailer->send();
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
