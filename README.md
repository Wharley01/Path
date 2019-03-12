# PHP Path

Path is an API-first PHP framework built with javascript in mind

## Contents

[Installation](#Installation) <br>
[Folder Structure](#Folder-Structure)<br>
[Your First API](#your-first-api)<br>
-----[Router](#router)

       




## Installation

create your project directory and initialize as git directory but running this command in that dir.
```bash
$ git init
```

pull Path's source to the directory you created with: 

```bash
$ git pull https://github.com/Wharley01/Path.git
```

If you are trying to download Path into an already existing git folder with unrelated history use:

```bash
$ git pull http://github.com/Wharley01/Path.git --allow-unrelated-histories
```

## Folder Structure

This are the folders you probably would be concerned with (unless you planning to contribute)

your-project-folder<br>
----<strong>path</strong>(your App's root folder) <br>
---------Commands<br>
---------Controllers<br>
------------Live<br>
------------Route<br>
---------Database<br>
------------Migration<br>
------------Models<br>
---------Http<br>
------------MiddleWares<br>

#### Explanation

`Commands` Contains all your CLI commands, (can be created using `php __path create command your_command_name` )<br>

`Controllers` contains all your project's `Route`and `Live` Controllers (Can be generated with `php __path create controller yourControllerName`)<br>

`Database` Folder contains database related codes, it has two folder which includes:<br>

1. `Migration` folder contains on all database migration files (can be generated using `php __path create migration yourDBtableName`)
2. `Models` folder contains all your database table models (can be generated during controller creation)

## Your First API

### Router

The backbone of every application is having an interface to interact with your data in the database, which is what will be demonstrated in this section.

To create an API you need a router which will listen to a particular Route(or URL) and appropriate action may be taken and a response will be returned(shown) to user.

you can listen to your preferred URL(Route) with Path's Router, for example:

 
 You initiate the use of router
 ```php
use Path\Http\Router;

$router = new Router();
 ```
 
 proceed to listening to a request
```php
 use Path\Http\Request;

 $router = new Router();
 
 $router->get("/your/custom/route",function(){
     //do something here
  });
```
The code above does two things, the first is to listen for `GET` request to `/your/custom/route`(i.e: http://yourproj.dev/your/custom/route) while the second is to execute a particular `function`.

The second argument may also be a `class` name. In this case, the `class` must have an accessible method/function `response` which 
serves as an entry point to your class after it has been instantiated.

   ```php
 use Path\Http\Request;

 $router = new Router();
 
  $router->get("/your/custom/route", RouteAction::class);
  ```
   
And the `class` may go like this

   ```php
    class RouteAction{
        ...
        public function response (Request $request, Response $response) {
            // your awesome stuff goes here
        }
        ...
    }
   ```

We can also listen for any request using the `Route::any` function. The usage of this function can be extended to automatically run a certain block of code depending on `$_SERVER['REQUEST_METHOD']`.

This is achieved by extending the `abstract Controller class`. An example is shown below 

```php
use Path\Controller;

class RequestHandler extends Controller{
    
    //This function runs automatically on Get request to the route
    public function onGet(Request $request, Response $response){
        //Your awesome stuff
    }
    
    //This function runs automatically on Post request to the route
    public function onPost(Request $request, Response $response){
       //Your awesome stuff
    }   
    //This function runs automatically on Delete request to the route
    public function onDelete(Request $request, Response $response){
       //Your awesome stuff
    }

}

```

And the request listening mechanism as shown below is same as the previous ones shown above

```php
$router->any('/route/my/route', RequestHandler::class)
```

NOTE: Using a custom class which extends the `abstract` `Controller` `class` on other route functions such as Route::post and Route::get will work fine, but it will only execute the function code that matches the REQUEST, Hence, this approach is not ideal.

However, It is a valid use case provided the Controller class is not extended. In this scenario, Path will default to executing the response method in the Supplied Class. An exception is thrown if Path can not find a response method in the provided class.

Path can also match dynamic url as seen below
```php
 use Path\Http\Request;

 $router = new Router();
 
 $router->get("/user/@id/profile",function(){
     //do something here
  });
```
Regular expressions is also a valid in url in the format shown below
```php
 use Path\Http\Request;

 $router = new Router();
 
 $router->get("/user/@id:[\d]+/profile",function(){
     //do something here
  });
```
There can be multiple routers listening to different routes with one `Router` object as shown below.

The `Router` object is instantiated once as `$router` and used to listen to more than one request.

```php
 use Path\Http\Request;
   
 $router = new Router(); //A router object is instantiated
 
  $router->get("/your/custom/route",function(){
     //do something here
  });
  
  $router->post("/another/custom/route",function(){
     //do something here
 });
   ```

Routes may also be grouped depending on your use case, as shown below.

```php
 use Path\Http\Request;
 use Path\Http\Router;

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
     })

  });
```

   ````
   NOTE: All routes codes must be written in path/Routes.php
   ````
   Now go on and visit http://yourproject.dev/your/custom/url you will get a json response
   
### Request
To interact with all request properties such as headers, url path properties, request types etc. Path is bundled with  a `class Request`. 

An instance of this class is instantiated on every request and passed as an argument to our function call in the `Router` request listening methods.
Hence, our previous definitions may be redefined as follows to enable access to the `Request` instance created.

```php
 use Path\Http\Request;
   
  ...
   
  $router->get("/your/custom/route",function(Request $request){
     //do something here
  });
  
  $router->post("/another/custom/route",function(Request $request){
     //do something here
 });
```
The `Request` object is particularly useful when we want to use the url path parameter argument, as seen in the introductory part of Routers.
We get the parameter defined in the url path from the $request object passed to our function.

```php
 use Path\Http\Request;
  
  ...
  
  $router->get("/user/@id:[\d]+/profile",function(Request $request){
     echo "Your user id is " . $request->param['id'];
     //do something here
  });
```

We can also get any header property using the `Request::fetch` method. Example is 

```php
  ...
  
  $request->fetch('REQUEST_METHOD'); // returns the request method
  
  $request->fetch('X-auth'); //Returns the value of the custom header
  
  //etc
  ...
```

If we're expecting a file as part of our request, We can get the file using the `Request::file` method.
```php
  ...
  
    $request->file('picture'); //Returns the array properties of the picture if valid
  
  //etc
  ...
```

### Response
For every request, a form of response is expected and necessary. Hurray!, Path makes this one easy for us too. Path is bundled with a `Response class` to send our preferred response properties such as headers, url path properties, request types etc.

Like the `Request class`, An instance of this class is also instantiated on every request and passed as an argument to our function call in the `Router` request listening methods as the second argument.

Hence, our previous definitions may be redefined as follows to enable access us the `Response` instance created for us.

```php
 use Path\Http\Request;
 use Path\Http\Response;
   
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
You may choose not to return anything if you preferred to handle the response yourself. 

Modifying and returning a response is easy. All you have to do is as follows

```php
 use Path\Http\Request;
 use Path\Http\Response;
   
  ...
   
  $router->get("/your/custom/route",function(Request $request, Response $response){     
     $response->json( ['status'=>'Successful', 'msg'=>'It works! Path does you good (^..^)'], $status = 200 )
     return $response;
  });
  
  $router->post("/another/custom/route",function(Request $request, Response $response){
     $response->text( "It works! Path does you good (^..^)", $status = 200 )
     return $response;
 });
```
```
Note: You may decide to create a new response object and return it instead (if you wish)
```
##### Usage
   Note that we used `$response->json()` method to return a json response in our callback function, which will execute when our route(`/your/custom/path`) is requested, there are many other available in `Response $response` as listed below:

   1. json( array $array, $status = 200 )
   2. text( string $text, $status = 200 )
   3. htmlString( string $html, $status = 200 )
   4. html( string $file_path, $status = 200 )
   5. redirect( string $route, $status = 200 )
   
   Methods that can be chained with the response method
      
   1. addHeader(array $headers)

# Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

# License
[MIT](https://choosealicense.com/licenses/mit/)
