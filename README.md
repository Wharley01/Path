# PHP Path

Path framework is an API-first PHP framework crafted for javascript.

Path framework is an MVC framework that uses your preferred javascript framework as the View while It handles the modelling and Controlling. Path framework is more Suitable for PWA and MVC modern web apps, Path can also be used to build just API for your existing App.

The focus of development Path is to avoid over-engineering and excessive abstraction

If you are someone that gets bored of a single language for both client and server sides just like me, Path is for you.

## List Contents

[Installation](#Installation) \
-----[Installation Method one](#Installation-Method-one)\
-----[Installation Method two](#Installation-Method-two)\
[Folder Structure](#Folder-Structure)\
[Your First API](#your-first-api)\
-----[Router](#router)\
--------[Request](#request)\
--------[Response](#response)\
--------[Middle Ware](#middle-ware)\
--------[Route Controller](#middle-ware)\
-----------[Request Hooks](#Using-Request-Hooks)\
-----------[Referencing methods in controller](#Using-String-literal)\
[Live Controller(RealTime Client-Server Communication)](#Live-Controller)\
-----[The WatcherInterface instance](#The-WatcherInterface-instance)\
-----[Client Side Integration](#Client-Side-integration)\
[Database Model](#database-model)\
-----[Reading Data](#Reading-data-from-the-Database)\
-----[Add constraint clause](#Adding-constraint-Clause)\
---------[Constraint Clause References](#constraint-methods-reference)\
-----[Updating table](#Updating-the-database)\
-----[Inserting into the database](#Inserting-to-the-database)\
-----[Working with JSON Column](#Path-DB-model-and-json)\
-----[Form Validation](#Form-Validation)\
[Path Email System](#Path-Email-System)\
-----[The mailable Class](#The-mailable-Class)\
-----[Sending email](#Sending-mail)\
---------[Mail\Sender methods](#Other-available-Sender-methods)\
-----[Mailer Configuration](#Mailer-Configuration)\
[Temporary Storages](#Temporary-Storages)\
-----[Sessions](#Sessions)\
-----[Cookies](#Cookies)\
-----[Caches](#Caches)\
[Command Line Interface](#Command-Line-Interface)\
-----[CLInterface methods](#CLInterface-methods)\
----------[Customizing Console text](#Customizing-Console-text)\
-----[Default Commands references](#Default-Commands-references)\
[Contributing](#Contributing)\
[Sponsor](#Sponsor)

## Installation

Create your project directory

```bash
mkdir yourProjectName
```

Then navigate to the folder you created

```bash
cd yourProjectName
```

Initiate folder as git repository

```bash
git init
```

### Installation Method one

Pull Path's source to the directory you created with:

```bash
git pull https://github.com/Wharley01/Path.git
```

If you are trying to download Path into an already existing git folder with unrelated history use:

```bash
git pull http://github.com/Wharley01/Path.git --allow-unrelated-histories
```

### Installation method two

Download your preferred version of Path on Path [releases Page](https://github.com/Wharley01/Path/releases)

## Folder Structure

These are the folders you probably would be concerned with (unless you are planning to contribute to Path's core development)

-**your-project-folder**\
----**path** `(your App's root folder)`\
...\
-------Commands\
-------Controllers\
----------Live\
----------Route\
-------Database\
----------Migration\
----------Models\
-------Http\
----------MiddleWares\
...

### Explanation

`Commands` Contains all your CLI commands, (can be created using `php __path create command your_command_name` )<br>

`Controllers` contains all your project's `Route`and `Live` Controllers (Can be generated with `php __path create controller yourControllerName`)<br>

`Database` Folder contains database related codes, it has two folders which includes:<br>

1. `Migration` folder contains on all database migration files (can be generated using `php __path create migration yourDBtableName`)
2. `Models` folder contains all your database table models (can be generated during controller creation)

## Your First API

### Router

The backbone of every application is; having a programmable interface for it, which may be consumed/used by your App or a third-party App.

To create an API you need a router which would be listening to a particular Route(or URL) and appropriate action may be taken and a response can be returned(shown) to the user.

you can listen to your preferred URL(Route) with Path's Router, for example:

You initiate the use of the router

```php
use Path\Core\Http\Router;

$router = new Router();
```

proceed to listen to a request

```php
 use Path\Core\Http\Router;

 $router = new Router();

 $router->get("/your/custom/route",function(){
     //do something here
  });
```

The code above does two things; the first is to listen for `GET` request to `/your/custom/route`(i.e., http://yourproj.dev/your/custom/route) while the second is to execute a particular `function` / `class method`.

Path can also match dynamic URL as seen below

```php
 use Path\Core\Http\Router;

 $router = new Router();

 $router->get("/user/@id/profile",function(){
     //do something here
  });
```

Regular expressions are also a valid parameter in URL using the format shown below

```php
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
 use Path\Core\Http\Request;

  ...

  $router->get("/user/@id:[\d]+/profile",function(Request $request){
     echo "Your user id is " . $request->param->id;
     //do something here
  });
```

Value of the `@id` parameter can be gotten using

```php
  ...
  $request->params->id//this depends on what you name your parameter
  ...
```

when `http://yourproject.dev/user/2323/profile` is requested, the value of `$request->params->id` becomes `2323`

header properties can also be fetched using the `$request->fetch()` method. Example is

```php
  ...

  $request->fetch('REQUEST_METHOD'); // returns the request method

  $request->fetch('X-auth'); //Returns the value of the custom header

  //etc
  ...
```

...Or a data sent via the HTML form

```php
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
  ...

    $request->file('picture'); //can be used with Path file System

  //etc
  ...
```

You may also use `Path\Core\Http\Request class` to request an external link using the simple API demonstrated below:

#### Sending Post Request

The example below sends a post request to Google with two pairs of parameter

```php
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
NOTE: In Path, Anywhere you are required to create a class, your `File` name must be the same as your `Class`'s name and each `Class` must be in a separate file.
```

```
TIPS: you may let path create the middleware code for you using the command "php __path create middleware yourMiddleWareName"
```


The code above describes two things for the router,

```php
public function validate(Request $request, Response $response):bool
```

1. The `validate()` method returns a `Boolean` which will be checked by the router(When a user tries to request it) if the returned `Boolean` is `True` the router proceeds with the request.

```php
 public function fallBack(Request $request, Response $response):?Response

```

2. if the validate() method's returned value is `False`, the router uses `fallBack()` method of your middleWare class as the route's response.

#### Using the MiddleWare with Route

After creating your middleWare class, the next thing to do is use the middleware with any of your preferred route as described below

```php
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
use Path\Core\Http\Router;

$router = new Router();

$router->get("/your/custom/route", RouteAction::class);
```

. . . And the Route Controller `class` may look like this

```php
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
use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Http\Router;

$router = new Router();

$router->get('fetch/user','UserController->methodName');


```
```
The Router goes to `path/Controllers/Route` and locate `UserController` controller, instantiate it, then reference its method `methodName`
```

## Live Controller

Path introduces a new approach to making a two-way real-time communication between the client-side and the server-side, wait, breath in, now out, lol, this is actually a very straightforward concept, it's basically you creating a class Called `Live Controller` that must extend an abstract class `Path\Core\Router\Live\Controller` in `path/Controllers/Live` folder with namespace `Path\App\Controllers\Live` and adding properties or methods then listen/watch for changes in your Live Controller Class's property's value or method's returned value from the client side, an example below shows how a super simple `Live Controller` Class looks like.

```php
/*
* This Live Controller File Was automatically 
* Generated by Path
* Modify to Suite your needs,
* */

namespace Path\App\Controllers\Live;

use Path\Core\Http\Response;
use Path\Core\Http\Watcher\WatcherInterface;
use Path\Core\Router\Live\Controller;
use Path\Core\Storage\Sessions;

class TestChanges extends Controller
{

    public $prop;


    //every time the watcher checks this Live Controller, it passes some data to it
    public function __construct(
        WatcherInterface  &$watcher,//watcher instance
        Sessions $sessions //the session instance that can be used for auth. with the client side
    )
    {

        $this->prop =  $sessions->get('is_logged_in') ? 'yes':'no';
    }
    public function prop(
        Response $response,
        WatcherInterface  &$watcher,
        Sessions $sessions
    ){
//response here will be sent to client side when $this->prop's value changes
        return $this->prop;
    }
    public function onMessage(
        WatcherInterface  &$watcher,
        Sessions $sessions
    ){
        //  this fires when there is a message from Client side(probably javascript?)
    }

    public function onConnect(
        WatcherInterface  &$watcher,
        Sessions $sessions
    ){

    }

    public function onClose(
        WatcherInterface &$watcher,
        Sessions $sessions,
        ?String  $message
    ){

    }
}
```

#### Code explanation

`On line 8` we declared this class's namespace to be the standard namespace for Live controller as mentioned earlier.

`On line 15` We named our `Live Controller` to be TestChanges.

`On line 18` we declared a property $prop to be populated later in the class's constructor and watched from the client side.

`On line 22` we have our constructor with parameters we are expecting from Path.

`On line 29` we populated the earlier created property `$prop` with yes/no based on the value of session `is_logged_in`

`On line 32` we have a method with the same name as our property `$prop`, this means we want to use this method's returned value for this property($prop) when the property($prop) changes; the value that will be sent to the client will be `prop()` method's returned value.

`On line 40` has an `onMessage` method which runs when there is a message from the client side.

`On line 48` also has another hook namely `onConnect` method which runs when the client-server connection is done successfully.

`On line 56` we have `onClose` method which runs when the connection is closed.

```
You can let Path create this Live Controller boilerplate code for you by using the command `php __path create controller yourControllerName` then choose 'Live' when asked the type of controller you are willing to create.
```

### The WatcherInterface instance

You probably saw `WatcherInterface &$watcher` in the example above and wondered what it does? `WatcherInterface` gives you access to info from [@__path/watcher javascript library](#Client-Side-integration). below is the list of the available list of methods you have access to:

| Method | Description|
|:-------|:------------|
| getMessage():?String |  Returns Message sent from client side or null if there is no message|
| getParams($key = null) | get parameter set to [@__path/watcher](#Client-Side-integration)|
| close():void | Close Connection |

### Client Side integration

After Setting up your Live Controller class on the server side, the next thing would be communication with it from the client side, this can be done using the [@__path/watcher](https://www.npmjs.com/package/@__path/watcher) javascript library, below is an example showing steps to communicate with or listen to the Live Controller class we created earlier:

```javascript
import Watcher from "@__path/watcher";

let watcher =  new Watcher('TestChanges')
//here we are referencing the class we created earlier
                   .watch('prop')//we told path-watcher which of the properties to watch, this case we are watching $prop property
                   .setParams({
                       key1:'a value',
                       anotherKey:'another value'
                   })//we are adding additional infos that can be retrieved from the server side with `getParam()` method of WatcherInterface instance.
watcher.onReady(watcher => {
    // here, we can assign functions to be executed when a particular property changes
    watcher.listenTo("prop", response => {
// we assign an anonymous function to execute when $prop changes on server side
//the `response` parameter will be an object where key "data" will be our property's value

        let is_logged_in = response.data;
// extract the data 
        if(is_logged_in !== 'yes'){
            location.href = '/logout'
        }
    })
})
watcher.start();//tell path to begin watching


```

The code above is well commented, if you need more information on this, quickly head to [its documentation](https://www.npmjs.com/package/@__path/watcher) on npm website, it contains the installation guide and usage.



## Database Model

What's the essence of an App without a Database? Useless? Well, maybe not all the time 😄, but either way, Path has a very flexible mechanism designed for you to interact with your Web App's database, The essence of Database Model it to give you the ability to configure how you want each table to be interacted with, like restricting column from being updated, setting columns that can be fetched and so on.

In Path Every Table in your database must be represented with a  `Class (Called Model)` which extends the `abstract Class Path\Core\Database\Model` which you will have to override its properties to suit your use case, a single database table can have multiple Database Model(if you want different set of rules for the same table). All database model must be created in `path/Database/Models` folder or its sub-folder.

A typical Database table model looks like this:

```php

/*
* This is automatically generated
* Edit to fit your need
* Powered By Path
*/

namespace Path\App\Database\Models;


use Path\Core\Database\Model;

class Test extends Model
{
    protected $table_name        = "your_table_name";
    protected $non_writable_cols = ["id"];//your primary key( default is "id")
    protected $non_readable_cols = [];//columns that can not be read(retrieved ) using this model(Test) instance

    public function __construct()
    {
        parent::__construct();
    }
}
```

#### Explanation

1. `protected $table_name = "your_table_name";` specifies database table for this model.

2. `protected $non_writable_cols = ["id"];` specifies the column that can not be changed(not writable)

3. `protected $non_readable_cols = [];` specifies which columns cannot be read(would be filtered out silently if you try to read/fetch them)

There are more model configurations which will be listed in the next sub-section

### Model Configuration reference

| Properties          | Default Value      | Description   |
| :------------------ | ------------------ | :------------ |
| \$primary_key       | `id`               | Specifies the Primary of your model's table(Defaul) |
| \$table_name        | null               | Holds Model table name |
| \$record_per_page   | 10                 | Total number of rows to return per page |
| \$non_writable_cols | []                 | The columns that can not be changed(not writable)                                     |
| \$non_readable_cols | []                 | The columns that cannot be read(would be filtered out if you try to read/fetch them)  |
| \$created_col       | `date_added`       | Specifies the column that holds your timestamp when a new data was inserted           |
| \$updated_col       | `last_update_date` | Specifies the table column name that holds the timestamp of when last the row updates |
| \$fetch_method      | `FETCH_ASSOC`      | PDO Method to use in fetch result                                                     |

```
NOTE: you may let path create the Database model code for you using the command "php __path create model yourModelName"
```

### Database Model usage

After configuring your database Model, you can go on using your Model by instantiating it, this way you have access to all the parent class `Path\Core\Database\Model`'s objects.

#### Reading data from the Database

Below is an example of fetching data from the database.

```php
...
use Path\Core\Database\Models;



// instantiate your model
// and fetch  all data from your `your_table_name` as indexed array
$test1 = (new Models\Test())
         ->getAll();

// fetch a particular set of columns instead

$test2 = (new Models\Test())
         ->select('name','age');//select just age and name

var_dump($test2->getAll());//return array of all data

var_dump($test2->getFirst());//return object of the first record only

var_dump($test2->getLast());//return object the last row only


...
```

##### Code explanation

1. `(new Models\Test())` instantiate your database model.

2. `->select('name','age')` specifies the columns you are interested in in this Model instance.

below them are demonstrated ways to fetch the result

1. `->getAll()` returns multi rows index based array of the data.

2. `->getFirst()` returns first single record in an associative array.

3. `->getLast()` returns last single record in an associative array.

#### Adding constraint Clause

Because you probably won't want to fetch/update/delete all data in your database table all the time, you can use available constraint methods to your advantage.

Below is an example of using a constraint clause while fetching data

```php
use Path\Core\Database\Models;

$fetch_data = (new Models/Test)
               ->select('name')//fetch only name column
               ->where('age = 12')//where age is 12 using `where` constraint method
               ->getFirst();//get the first record only

var_dump($fetch_data);

$fetch_data2 = (new Models/Test)
               ->where("name")
               ->like("%ade")
               ->orWhere("age > 40")
               ->batch(1,10)//from 1st record to 10th
               ->getAll();

```

##### Code explanation

In the code above, we made use of constraint clauses to further describe the kind of data we want, there are more constraint methods available which is listed below.

#### Advantage

Are you probably thinking why can't I just write a raw query? yes, you can, but it's fatally insecure, Path's query builder binds your data with your query out of the box, which gives you the freedom to make your SQL query programmable and at the same time enjoy the maximum security.

#### constraint methods reference

| Clause                       | Possible values  | Description | Example |
| :--------------------------- | :--------------- | :---------- | :------ |
| `where(mixed $condition)`    | column name, condition (i.e: age > 12) or an associative array(i.e: ['name' => 'adewale']), json reference (i.e: 'column->obj->property') | This defines the WHERE clause to filter records | 1. `where("column")->like("%a")` <br><br>2.`where("column")->notLike("e%")` <br><br> 3.`where("column")->between(10,20)`<br><br>4.`where("age > 20")`<br><br>5. `where(["name" => "John Doe"])`<br>`where('column')->in(['item1','item2','item3'])` |
|`rawWhere(mixed $condition,...$params)`|column name, condition (i.e: age > 12) or an associative array(i.e: ['name' => 'adewale']), json reference (i.e: 'column->obj->property')|This defines the WHERE clause to filter records| `rawWhere('age > ?',60)`<br>`rawWhere('age > ? AND name = ?',60,'adewale')` |
| `orWhere`                    | column name, condition (i.e: age > 12) or an associative array(i.e: ['name' => 'adewale']) | Define Alternative condition, equivalent to SQL's "`OR WHERE`" clause  | `where("score > 50")->orWhere("is_passed")`  |
| `like(String $wild_card)`    | All SQL wildcard syntax (i.e: %value%)  | Describes the LIKE wild card, equivalent to SQL's "`WHERE column LIKE '%value'`", `like()` method should always be combined with `where()` method which will specify the column in this context     | `where("column_name")->like("%a")` |
| `notLike(string $wild_card)` | All SQL wildcard syntax (i.e: %value%)   | Describes the `NOT LIKE` wild card, equivalent to SQL's "WHERE column NOT LIKE '%value'", like() method should always be combined with where() method which will specify the column in this context | where("column_name")->notLike("%a")  |
| `in(Array $array)` | list of items in array | specifies list of items a column must include | where('column')->in(['item1','item2','item3']) |
|`select(...$columns)`|db table column to select, json reference (i.e: 'column->obj->property') and/or raw select | Specifies which column you are interested in at the moment|1.  `select('username','password')->where('age > 20')`<br> 2. `select('profile->first_name')->as('first_name')`|

#### Updating the database

It's pretty straight forward to update data with Path, an example below shows how that can be done

```php
use Path\Core\Database\Models;
//updating all data in database table associated to Test model
$update = (new Models/Test)->update([
   "name" => "Adewale"
]);

//adding constraint clause
$update = (new Models/Test)
      ->where("id = 334")//updates column with id 334 only 
      ->update([
         "name" => "Adewale"
         ]);
```

#### Inserting to the database

Below example shows how adding new data to your database is done

```php
use Path\Core\Database\Models;

$update = (new Models/Test)->insert([
   "name" => "Adewale"
]);

```


Note that when you insert into a database, Path automatically adds appropriate values to  date_added, id and last_update_date, these column names depends on what you set in your [model configuration](#Model-Configuration-reference).  

#### Path DB model and json

The support for JSON column started with Mysql 5.7, there is support for JSON in Path's database query builder.

If you are using the appropriate version of mysql go on and make use of Path's query builder. Below example shows some possibilities.

##### selecting a json object's key value

```php
use Path\Core\Database\Models;
//this example assumes the value of `profile` column  to be:
/*
* {
   "name":"...",
   "age":"102",
   "school":"..."
   "pictures":{
      "cover":"...",
      "avatar":"..."
   }
   ...
}
*/
$select = (new Models/Test)
            ->select('profile->name')->as('name')
            ->getFirst();

$select = (new Models/Test)
            ->select('profile->pictures->cover')->as('cover_picture')
            ->getFirst();
```

##### Updating JSON content of a JSON column

```php
use Path\Core\Database\Models;

//this will update json content of the profile column
$update = (new Models/Test)
            ->update([
               "profile->name" => "Adewale"
               ]);

```

##### Referencing JSON object key's value in WHERE clause

```php
use Path\Core\Database\Models;
//using json key's valuein Where clause

$select = (new Models/Test)
            ->where('profile->age > 18')
            ->orWhere([
               'profile->gender' => 'female'
               ])
            ->getFirst();

```

## Database migration

Managing database tables is stressful, having to import sql files on every installation is tiring and needs automation, for this reasons Path comes with a mechanism to automate your database installation called `Database Migration`, a Database migration file represents each of the tables in your applications database `(Note: this is different from Database Model)` and are saved in `path/Database/Migration`, in it are where table columns are described, a typical database migration file looks like this:

```php

namespace Path\App\Database\Migration;


use Path\Core\Database\Model;
use Path\Core\Database\Prototype;
use Path\Core\Database\Structure;
use Path\Core\Database\Table;


class User implements Table
{
    public $table_name = "user";
    public $primary_key = "id";
    public function install(Structure &$table)
    {
       //this runs when you run php __path app install
        $table->column("full_name")
            ->type("text");

        $table->column("email")
            ->type("text");

        $table->column("session_hash")
            ->type("text")
            ->nullable();

        $table->column("user_name")
            ->type("text");

        $table->column("is_admin")
            ->type("boolean")
            ->default(0);  

    }

    public function uninstall()
    {
    }

    public function populate(Model $table)
    {
       //this runs when you run php __path app populate
        $table->insert([
           "user_name" => "Adewale",
           "email"    => "adewale@domain.com"
        ]);

    }

    public function update(Structure &$table)
    {
       //this runs when you run php __path app update

    }
}

```

You probably wouldn't have a need to create these files yourself as there are available [CLI commands](#CLI-commands) to automate this, for example, to create a migration file, simply run `php __path create migration yourMigrationName`

### configuring database columns

To let Path know the columns to add when you `php __path app install`, you have to specify them in the `install(Structure &$table)` method of your migration file as done in the example above

### updating database columns configuration

When adding a column or updating it's property do so in the `update(Structure &$table)` method without changing anything in the `install(Structure &$table)`(if you've already run the `php __path app install`), Path handles every update.

```
If you add a column that isn't already among the DB columns to update(), Path will see it as a new column and add it to your table.
```

#### updating column's properties

By combining `rename()`, `to()` and `update()` method you can rename a column, an example is shown below

```php
namespace Path\App\Database\Migration;


use Path\Core\Database\Model;
use Path\Core\Database\Prototype;
use Path\Core\Database\Structure;
use Path\Core\Database\Table;

...

    public function update(Structure &$table)
    {
       //this runs when you run php __path app update
       $table->rename('column')->to('new_column_name')
             ->update();
             
       $table->column('a_text_column')
             ->type('int')//this will change a previously text column to integer.
             ->update();//tells Path to update, else Path will ignore.
    }

...

```

### Deleting column

By Appending the dropColumn() to a column you tell Path to delete the column. An example is shown below:

```php
namespace Path\App\Database\Migration;


use Path\Core\Database\Model;
use Path\Core\Database\Prototype;
use Path\Core\Database\Structure;
use Path\Core\Database\Table;

...

    public function update(Structure &$table)
    {
       //this runs when you run php __path app update
       //column_to_delete column will be removed when "php __path app update" is ran
       $table->column('column_to_delete')
             ->dropColumn();

    }

...

```

### Installing database migration

This basically means: adding all necessary tables and columns, there are few commands needed to do this operation:

1. `php __path app install` installs all database migrations.

2. `php __path app install yourMigrationName` install a particular migration file

### UnInstalling database migration

This is a very fragile operation, this will remove all data and table depending on which of the commands you run.


1. `php __path app uninstall` deletes all tables and its data (be careful with this command in production).

2. `php __path app uninstall yourMigrationName` deletes `yourMigrationName`'s table and its data (be careful with this command in production).

### Updating database migration

1. `php __path app update` updates all database migrations(based on what's inside the `update()` method for each file).

2. `php __path app update yourMigrationName` updates a particular migration file (based on what's inside the `update()` method for each file)

___

Note that you can combine all this command as you want, for example, to install and update all migration files on-the-go, you can run `php __path app install update`, or even `php __path app install yourMigrationName update anotherMigrationName`

___



## Form Validation

Path provides a simple and straight forward API for you to validate forms or inputs from user using the `Path\Core\Misc\Validator` class, to validate a post/get param you need a pair of `key(string $name)` and `rules(...$rules)`, the `rules(...$rules)` method accepts some `Path\Core\Misc\Validator` static methods to describe the rules for a particular parameter/key/field, the example below shows a simple registration form validation:

```
examples in this section assume below listed request parameters/fields were sent from the client:

username
email
password
re_password
```


```php
namespace Path\App\Controllers\Route;

use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Misc\Validator;
use Path\Core\Router\Route\Controller;

class User 
{
   public function response(
      Request $request, 
      Response $response
      ){
         $post_data = $request->getPost();

         // do validation

         $validator = new Validator($post_data);

         // make username required
         $validator->key('username')
                   ->rules(
         //this is required
                      Validator::REQUIRED('Username is required'),//error message as parameter

         //Username must not be less than 5 characters
                     Validator::MIN(
                        5,
                        'Username must nor be less than 5 character'
                     )
                   );
         
         $validator->key('email')
                   ->rules(
                      Validator::REQUIRED(),//will generate error message for you
                      Validator::FILTER(
                         FILTER_VALIDATE_EMAIL,//you can use PHP's Validation constant
                         'Invalid Email provided'
                         )
                   );

         $validator->key('password')
                   ->rules(
                      Validator::MIN(
                         6,
                         'Minimum Password length is 6'
                         )
                   );
         $validator->key("re_password")
                  ->rules(
                     Validator::EQUALS(//referencing one of the fields
                        'password',
                        'retyped password must be equal to password'
                     )
                  )->validate();//notice this trailing ->validate() method, must be at the very end of all your validations
         //you can go on with checking for errors
         if($validator->hasError()){
            // you can get those errors here
            print_r($validator->getErrors());
         }
   }
}


```

Assuming there was an invalid input in the example below, this is what the print_r() above would look like this:

```php

Array(
    [username] => Array(
            [0] => Array(
                    [msg] => Username must not be less than five character  
                )
            )
    [email] => Array(
            [0] => Array(
                    [msg] => email field must be a valid email
                )
        )
    [re_password] => Array(
            [0] => Array(
                    [msg] => retyped password must be equal to password
                )
        )
)
```
___
The getErrors() returns an array of errors, where params/form fields are the keys and values are their array of errors.
___

## Path Email System

Path also made it possible to send emails with ease, either using the native mail() support or SMTP.

### The mailable Class

The first thing is creating your mail template/Mailable to let you reuse them, you can do so by running `php __path create email yourMailableName`, a code will be generated for you in `path/Mail/Mailables` folder, the file looks like this:

```php

namespace Path\App\Mail\Mailables;

use Path\Core\Mail\Mailable;
use Path\Core\Mail\State;


class TestMail extends Mailable
{

    /*
    * Change this recipient details or set dynamically
    */
    public $to = [
        "email" => "recipient@provider.com",
        "name"  => "Recipient name"
    ];

    /**
     * TestMail constructor.
     * @param State $state
     */
    public function __construct(State $state)
    {
    }

    public function title(State $state):String
    {
        return "this is the title";
    }

    public function template(State $state):String
    {
        return "Hello {$state->name}";
    }

}
```

### Sending mail

Using the initially created Mailable file combined with `Path\Core\Mail\Sender` Class, you can send several emails with different states(data), the example below shows its usage:

```php
use Path\Core\Mail;
use Path\App\Mail\Mailables;

$mailer = new Mail\Sender(
                  Mailables\TestMail::class
               );//where TestMail is your mailable class

// you can bind a state to your mailable this way
$mailer->bindState([
        "name" => "Testing Testing"
    ]);//TestMail has access to name property in the 'Path\Core\Mail\State $state' method argument
$mailer->send();//sends the email,

```

#### Other available Sender methods

There are other useful methods can be used with the Mail\Sender, they are listed below:

1. `setFrom(array $from)` sets the mail's sender's details, if this is not set, it will use the Mailable's `$to` property or fallback to Admin details in the config(`path/project.pconf.json`).

2. `setTo(array $to)` sets the details(email and name) of whom to send this particular mail to, is this method is not called, it uses the Mailable's `$to`  property

2. `hasError():bool` returns true if there was an error

3. `getTo():array` returns array of details of the recipient.

4. `getFrom():array` returns an array of details of the sender details.

### Mailer Configuration

What helps Path decide which method to use in sending emails are configured in `path/project.pconf.json`, below is how it looks like:

```json
  "MAILER":{
    "USE_SMTP": false,
    "SMTP":{
      "host":"",
      "username":"",
      "password":"",
      "port": 0,
      "protocol":"",
      "charset":"UTF-8"
    },
    "ADMIN_INFO":{
      "email":"admin@__path.com",
      "name": "Path Admin"
    }
  },
```

___
If `USE_SMTP` is set to `true`, Path uses the SMTP configuration beneath it, this config also has the default admin info, this will be used when `from` email details are not specified(sort of fallback details). 
___

## Temporary Storage

There several ways to hold data temporarily in Path, this section explains each of them.

### Sessions

Sessions are temporary and local to each user, which means one user can't read another's Session, to access session you need to instantiate the `Path\Core\Storage\Sessions`, see usage example below:

```php
use Path\Core\Storage\Sessions;

$session = new Sessions();

// you save a session
$session->store('key','value');
//get a saved a session
$session->get('key');
//delete a session
$session->delete('key');
// overwrite a session
$session->overwrite('key','value');
// returns all session data as array
$session->getAll('key','value');


```

### Cookies

A Cookie is temporary storage but lasts longer than a session, unlike sessions, Cookies does not clear when the users close their browsers, Cookies clear only when the expiration time you set passes or the user manually clears their cookie. the example below shows how cookies can be interacted with in Path.

```php
use Path\Core\Storage\Cookies;

$cookie = new Cookies(Cookies::ONE_DAY);//there are more static helpers, will be listed below this example

// you save a Cookie
$cookie->store('key','value');
//get a saved a Cookie
$cookie->get('key');
//delete a Cookie
$cookie->delete('key');
// overwrite a Cookie
$cookie->overwrite('key','value');
// returns all Cookie data as array
$cookie->getAll('key','value');


```

There are more Durations helper static methods or constants, few of them are listed below:

| Methods/Properties | Functionality
---------------------| ---------------
| `Cookies::ONE_DAY` | Cookie expires in one day
| `Cookies::ONE_WEEK`| Cookie expires in one week
| `Cookies::DAYS(int $days)` | Specifying number of days
| `Cookies::WEEKS(int $weeks)` | Specifying number of weeks
| `Cookies::MONTHS(int $months)` | Specifying number of Months

### Caches

Caches are permanent unless cleared, it's not meant to be used for sensitive data as can be leaked, caches are stored in `path/.Storage/.Caches/` folder, examples below shows usage of Path's caches.

```php
use Path\Core\Storage\Caches;

// Caches does not need instantiation, every method is static

//cache a data
Caches::cache('key','Value');
//get cached data
Caches::get('key');
//deleting cached data
Caches::delete('key');
//delete All Caches
Caches::deleteAll();

```

## Command Line Interface

The support for command line interface in Path is very straight-forward and super simple, as usual, it only takes creating a class in `path/Commands` with an interface of `Path\App\Commands` extending `Path\Core\CLI\CInterface` or let Path create it for you using the `php __path create command yourCommandFileName`. it's that simple, a typical Command Line File looks like this:

```php


namespace Path\App\Commands;


use Path\Core\CLI\CInterface;

class TestCommand extends CInterface
{


    /*
     * Command Line name
     *
     * @var String
     * */
    public $name = "Test";
    public $description = "This is just a test command interface file";

    public $arguments = [
        "key" => [
            "desc" => "just a test key"
        ]
    ];

    public function __construct()
    {
    }

    /**
     * @param $params
     * @return mixed|void
     */
    public function entry($params)
    {
        var_dump($params);
    }

```

#### Code explanation

1. `public $name` holds the command name (which is expected to be typed in the terminal after `php __path`)

2. `public $description` holds the description of this command, this will be shown when you run `php __path explain yourCommandName`

3. `public $arguments` hold an associative array of acceptable params `( Please note that parameters not explicitly specified here will be ignored or seen as a value )`

4. `public function entry($params)` executes when you run `php __path yourCommandName` with $params being an associative array where the key is the parameter and value is the text immediately next to the key in the terminal, for example, if you run:

```bash
php __path Test something key "another value with space"
```

The dumped value in entry() will look like this:

```php
array(2) {
  ["Test"]=> string(9) "something"
  ["key"]=> string(24) "another value with space"
}
```

if a key is included without a succeeding text, the key's value will be null

There are some inherited methods that can be handy, the table below shows them and their usage

### CLInterface methods

| Method | Description |
|:------ | ----------- |
| `ask(string $question,bool $enforce = false)` | Retrieves information/input from user through the console, returns user's input, null if no input was provided by the user|
| `confirm(string $question,array $yes_text = ['yes','y'],array $no_text = ['no','n'])` | Gets binary yes/no options from user,first argument sets the question to ask, the second argument accepts array of texts that passes as Yes, while the third argument accepts texts that passes as No |
| write(string $text,$format) | Writes to the console, There are ways to customize your text using a predefined pattern, read explanation below this table. |


#### Customizing Console text

To apply a custom colour to your text, you have to wrap it with \`color_name\`, below example shows writing a red and blue text to the console.

```php
...
    public function entry($params)
    {
        $this->write("`red`The is a red text`red` `blue`This another text with a blue color`blue`")
    }
...

```

running `php __path Test` would produce\
\
![php __path Test](./docs/images/doc-console-text-color-image.jpg)

##### Available colors

| Colors |
|------- |
| black   |
| dark_gray |
| blue |
| light_blue |
| green |
| light_green |
| cyan |
| light_cyan |
| red |
| light_red |
| purple |
| light_purple|
| brown |
| yellow |
| light_gray |
| white |
| normal |

### Default Commands references

Below is the table of commands that are available by default
| Command | Usage |
| ------- | ----- |
| `php __path explain` or `php __path explain command:name` | show all available commands and their explanations or shows a specific command's explanation.|
|`php __path create controller yourControllerName` | Create Route/Live Controller |
| `php __path create command yourCommandName` | create new CLI Class and generates boilerplate code |
| `php __path create migration yourMigrationName` | Creates new database migration file/boiler plate |
| `php __path create middleware middleWareName` | Creates new middle ware boilerplate code file |
| `php __path create model yourTableModelName` | Generates a database table model boilerplate code |
|  `php __path create email MailableName` | Creates mailable boiler plate code|
| `php __path app install` or `php __path app install dbMigrationName` | install all database migration files or install a specific one| 
| `php __path app update` or `php __path app update dbMigrationName` | update all database migration files or update a specific one|
| `php __path app uninstall` or `php __path app uninstall dbMigrationName` | uninstall all database migration files or uninstall a specific one|
| `php __path app populate` or `php __path app populate dbMigrationName` | populate all database migration files or populate a specific one|
| `php __path app describe` or `php __path app describe dbMigrationName` | print/describe the database structure table into the console|
| `php __path watcher start` | Start watcher server (Needed if you are using Path Live Controller and WS as watch method in your configuration)|

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## Sponsor

If you love this project and  you'd like to keep this project by contributing to the developer, kindly sponsor my account through the github sponsor.

## License

[Apache License 2.0](https://choosealicense.com/licenses/apache-2.0/)
