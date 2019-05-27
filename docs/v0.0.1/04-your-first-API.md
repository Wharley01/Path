## Your First API

### Router

The backbone of every application is; having a programmable interface for it, which may be consumed/used by your App or a third-party App.

To create an API you need a router which would be listening to a particular Route(or URL) and appropriate action may be taken and a response can be returned(shown) to the user.

you can listen to your preferred URL(Route) with Path's Router, for example:

You initiate the use of the router

```php
<?php

use Path\Core\Http\Router;

$router = new Router();
```

proceed to listen to a request

```php
<?php

 use Path\Core\Http\Router;

 $router = new Router();

 $router->get("/your/custom/route",function(){
     //do something here
  });
```

The code above does two things; the first is to listen for `GET` request to `/your/custom/route`(i.e., http://yourproj.dev/your/custom/route) while the second is to execute a particular `function` / `class method`.

Path can also match dynamic URL as seen below

```php
<?php

 use Path\Core\Http\Router;

 $router = new Router();

 $router->get("/user/@id/profile",function(){
     //do something here
  });
```

Regular expressions are also a valid parameter in URL using the format shown below

```php
<?php

 use Path\Core\Http\Router;

 $router = new Router();

 $router->get("/user/@id:[\d]+/profile",function(){
    //[\d]+ enforces @id to be a digit of at least one character
     //do something here
  });
```

There can be multiple routers listening to different routes with one `Router` object as shown below.

The `Router` object is instantiated once as `$router` and used to listen to more than one request.

```php
<?php

 use Path\Core\Http\Router;
 use Path\App\Controllers\Route\MyController;
 $router = new Router(); //A router object is instantiated

  $router->get("/your/custom/route",function(){
     //do something here
  });

  $router->any("/another/custom/route",MyController::class);//this will be discussed later

  $router->post("/yet/another/route","AnotherControllerClass->aMethod");



```

Routes may also be grouped depending on your use case, as shown below.

```php
<?php

 use Path\Core\Http\Router;

 $router = new Router();

  $router->group("/api/",function(Router $router){
   //   `$router` variable here is localized to this callback function
     //you can put multiple routes here
    $router->get("/user/custom/route",function(){
     //do something here
     //this listens to /api/user/custom/route
     });

     $router->post("/admin/route",function(){
     //do something here
     //this listens to /api/admin/route
     });

     //It's also possible to group routes within another group
     $router->group("update", function(Router $router){
        //Further routing goes here
        $router->get("/yet/another/sub/route",function(){

        })
     })

  });
```

```
NOTE: All routes codes must be written in `./path/Routes.php`
```

Now go on and visit http://yourproject.dev/your/custom/url you will get a JSON response



### Request

To interact with all request properties such as headers, URL path properties, request types etc. Path is bundled with a `class Request`.

An instance of this class is instantiated on every request and passed as an argument to our function call in the `Router` request listening methods.
Hence, our previous definitions may be redefined as follows to enable access to the `Path\Core\Http\Request` instance created.

```php
<?php

 use Path\Core\Http\Request;
 use Path\Core\Http\Router;

  ...

  $router->get("/your/custom/route",function(Request $request){
     //do something here or use $request here
  });

  $router->post("/another/custom/route",function(Request $request){
     //do something here
 });
```

The `Request` object is particularly useful when we want to use the URL path parameter argument, as seen in the introductory part of Routers.
We get the parameter defined in the URL path from the \$request object passed to our function.

```php
<?php

 use Path\Core\Http\Request;

  ...

  $router->get("/user/@id:[\d]+/profile",function(Request $request){
     echo "Your user id is " . $request->param->id;
     //do something here
  });
```

Value of the `@id` parameter can be gotten using

```php
<?php

  ...
  $request->params->id//this depends on what you name your parameter
  ...
```

when `http://yourproject.dev/user/2323/profile` is requested, the value of `$request->params->id` becomes `2323`

header properties can also be fetched using the `$request->fetch()` method. Example is

```php
<?php

  ...

  $request->fetch('REQUEST_METHOD'); // returns the request method

  $request->fetch('X-auth'); //Returns the value of the custom header

  //etc
  ...
```

...Or a data sent via the HTML form

```php
<?php

  ...
  //equivalent to $_REQUEST['input_name']
   $request->fetch("input_name");

  //equivalent to $_POST['input_name']
   $request->getQuery("input_name");

  //equivalent to $_POST['input_name']
   $request->getPost("input_name");

  ...
```

Files sent via form can be retrieved using the `$request->file()` method.

```php
<?php

  ...

    $request->file('picture'); //can be used with Path file System

  //etc
  ...
```

You may also use `Path\Core\Http\Request class` to request an external link using the simple API demonstrated below:

#### Sending Post Request

The example below sends a post request to Google with two pairs of parameter

```php
<?php

use Path\Core\Http\Request;

try{
    $request = new Request();//initiate request

    $request->setPostFields([
        "fname" => "adewale",
        "lname" => "Sulaiman"
    ]);

    var_dump($request->post("https://google.com/"));//this returns a response object
}

```

The variable dump above returns

```php
<?php


object(Path\Core\Http\Response)#5 (5) {
  ["content"]=> String(1555) "Webpage content"
  ["status"]=> int(200)
  ["headers"]=> array(4) {
    ["content-type"]=> string(24) "text/html; charset=UTF-8"
    ["referrer-policy"]=> string(11) "no-referrer"
    ["content-length"]=> string(4) "1555"
    ["date"]=> string(29) "Sun, 05 May 2019 14:11:58 GMT"
  }
  ["build_path"]=> string(1) "/"
  ["is_binary"]=> bool(false)
}

```

#### Sending GET Request

The example below sends a 'get' request to Google with two pairs of parameter

```php
<?php

use Path\Core\Http\Request;

try{
    $request = new Request();//initiate request

    $request->setQueryParams([//this is setQueryParams instead of setPostParams
        "fname" => "adewale",
        "lname" => "Sulaiman"
    ]);

    var_dump($request->get("https://google.com/"));
    //https://google.com/ becomes https://google.com/?fname=adewale&lname=sulaiman

    //this returns a response object
}

```

```
You may use any request type by changing the request method to suit your use case.
```


### Response

For every request, a form of response is expected and necessary.  Path is bundled with a `Path\Core\Http\Response class` to send your preferred response properties such as headers, content and response types back to the user.

Like the `Request class`, An instance of this class is also instantiated on every request and passed as an argument to your function/method call in the `Router` request listening methods as the second argument.

Hence, our previous definitions may be redefined as follows to enable access us the `Response` instance created for us.

```php
<?php

 use Path\Core\Http\Request;
 use Path\Core\Http\Response;

  ...

  $router->get("/your/custom/route",function(Request $request, Response $response){
     //modify response here
     return $response;
  });

  $router->post("/another/custom/route",function(Request $request, Response $response){
     //modify response here
     return $response;
 });
```

We have returned the `$response` above to let Path know there's a response (i.e `Response`) it has to give back to the browser.
You may choose not to return anything if you preferred to handle the request's response yourself.

Modifying and returning a response is easy. All you have to do is as follows

```php
<?php

 use Path\Core\Http\Request;
 use Path\Core\Http\Response;

  ...

  $router->get("/your/custom/route",function(Request $request, Response $response){
     $response->json([
        'status' => 'Successful',
        'msg' => 'It works! Path does you good (^..^)'
        ], $status = 200 );
     return $response;
  });

  $router->post("/another/custom/route",function(Request $request, Response $response){
     
     return $response->text("It works! Path does you good (^..^)", $status = 200 );
 });

```

```
Note: You may decide to create a new response object and return it instead (if you wish)
```





#### Usage Reference

Note that we used `$response->json()` method to return a json response in your callback function, which will execute when your route(`/your/custom/path`) is requested, there are many other available methods in `Response $response` as listed below:

1. json( array $array, $status = 200 )

2. text( string $text, $status = 200 )

3. htmlString( string $html, $status = 200 )

4. html( string $file_path, $status = 200 )

5. redirect( string $route, $status = 200 )

Methods that can be chained with the response method

1. addHeader(array \$headers)

### Middle Ware

The MiddleWare Concept was designed to add a restriction(s) to the accessibility of a particular route based on your preferred condition when needed, this can be very handy in cases like the restriction of API usage for the non-logged user, API-key system, request form validation and many more.

A middleware, when specified is first executed and its conditions checked before Path would decide whether to proceed with the request of fallback to the middleWare's fallback method.

#### The MiddleWare Class

In Path, MiddleWares must me created as a class in `path/Http/MiddleWares/` folder and your class must implement `Path\Http\MiddleWare`, a typical middleware class Looks like this:

```php
<?php

namespace Path\App\Http\MiddleWares;


use Path\Core\Http\MiddleWare;
use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Storage\Sessions;

class isProd implements MiddleWare
{


    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @throws \Path\Errors\Exceptions\Config
     * @internal param $params
     */
    public function validate(Request $request, Response $response):bool
    {
        return config("PROJECT->status") == "production";
    }

    public function fallBack(Request $request, Response $response)
    {
            return $response->json(['mode' => 'Development Mode',"session_id" => session_id()]);
    }
}
```

```
NOTE: In Path, Anywhere you are required to create a class,
 your `File` name must be the same as your `Class`'s name and each `Class` must be in a separate file.
```

```
TIPS: you may let path create the middleware code for you using the command "php __path create middleware yourMiddleWareName"
```


The code above describes two things for the router,

```php
<?php

public function validate(Request $request, Response $response):bool
```

1. The `validate()` method returns a `Boolean` which will be checked by the router(When a user tries to request it) if the returned `Boolean` is `True` the router proceeds with the request.

```php
<?php

 public function fallBack(Request $request, Response $response):?Response

```

2. if the validate() method's returned value is `False`, the router uses `fallBack()` method of your middleWare class as the route's response.

#### Using the MiddleWare with Route

After creating your middleWare class, the next thing to do is use the middleware with any of your preferred route as described below

```php
<?php

...
use Path\App\Http\MiddleWares\isProd;

$router->get([
   "path" => "/your/awesome/route",
   "middleware" => isProd::class//this can be array of MiddleWares too
   ],function(){
// this will execute if the middleware "isProd"'s validate() method returns true

   })
...
```

You can use multiple middleWares this way:

```php
<?php

...
use Path\Core\Http\MiddleWare\IsValidPost;
use Path\Core\Http\MiddleWare\IsLoggedUser;

use Path\Core\Http\Router;

$router = new Router();
$router->get([
   "path" => "/your/awesome/route",//this can be array of paths too
   "middleware" => [
      IsLoggedUser::class,
      IsValidPost::class
   ]//multiple middleWares are placed in array
   ],function(){
// this will execute if all the middleWares in the return true

   })
...

```

MiddleWares validation is done in order of how you place them in the array and appropriate response is sent to the user based on the currently failing(returning false) MiddleWare, Proceeds to other when current one passes(returns true).



### Route Controller

to be able to keep all your logic outside the Routes, Path provides an abject Oriented to Route Controlling.

#### Using custom Class

The second argument of your Router method may also be a `class` called 'Route Controller'. In this case, the `class` must have an accessible method `response` which
serves as an entry point to your class after it has been instantiated.

For example:

```php
<?php

use Path\Core\Http\Router;

$router = new Router();

$router->get("/your/custom/route", RouteAction::class);
```

. . . And the Route Controller `class` may look like this

```php
<?php

 namespace Path\App\Controllers\Route;

 use Path\Core\Http\Request;
 use Path\Core\Http\Response;

 class RouteAction{
     ...
     public function response (Request $request, Response $response) {
         // your awesome stuff goes here
     }
     ...
 }
```

#### Using Request Hooks

We can also listen for any type of request (i.e: GET/POST/PUT e.t.c) using the `Route->any()` method. The usage of this function can be extended to automatically run a certain block of code depending on `$_SERVER['REQUEST_METHOD']`(Request type).

This can be achieved by extending the `abstract Path\Core\Router\Route\Controller class`. An example is shown below

```php
<?php

use Path\Core\Router\Route\Controller;

class RequestHandler extends Controller{

    //This function runs automatically on 'Get' request to the route
    public function onGet(
       Request $request,
       Response $response
       ){

        //Your awesome stuff
    }

    //This function runs automatically on Post request to the route
    public function onPost(
       Request $request,
       Response $response
       ){

       //Your awesome stuff

    }
    //This function runs automatically on Delete request to the route
    public function onDelete(
       Request $request,
       Response $response
       ){

       //Your awesome stuff

    }
    ...

}

```

The Controller Class Above can be used with any request as shown below:

```php
<?php

$router->any('/route/my/route', RequestHandler::class)
```

The method in the class `RequestHandler` that will serve as response for `'/route/my/route'` depends on `$_SERVER['REQUEST_METHOD']`, for example:

if a GET request is sent to `'/route/my/route'` the `public function onGet(){}` serves as the response. a `Exceptions\Router` will be thrown if required method for the user's current `request method` is not found in your `Controller` Class

```text
NOTE: Using a custom class which extends the `abstract` `Controller` `class` on other route methods except `any()` such as `$route->post()` and `$route->get()` will work fine, but it will only execute the function code that matches the REQUEST, Hence, this approach is not ideal.
```

However, It is a valid use case to use a class that's not extending `Controller abstract class`. In this scenario, Path will default to executing the response method in the Supplied Class. An `Exceptions\Router` is thrown if Path cannot find a response method in the provided class.

#### Using String literal

It's possible to manually reference a particular method in your Controller class to serve as the response, the example below shows how it's done.

```php
<?php

use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;

$router = new Router();

$router->get('fetch/user','UserController->methodName');


```
```
The Router goes to `path/Controllers/Route` and locate `UserController` controller, instantiate it, then reference its method `methodName`
```
