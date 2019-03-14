# PHP Path

Path framework is an API-first PHP framework crafted for javascript.

 Path framework is an MVC framework that uses your preferred javascript framework as the View while It handles the modeling and Controlling. Path framework is more Suitable for PWA and MVC modern web apps, can also be used to build just API for your existing App.

 If you are someone that gets bored of single language on both client and server sides just like me, Path is for you. 

Path framework can also be used to build real-time Apps. While all your data are still within your server (Seems like a better alternative to firebase?)

## Contents

[Installation](#Installation) <br>
[Folder Structure](#Folder-Structure)<br>
[Your First API](#your-first-api)<br>
-----[Router](#router)<br>
--------[Request](#request)<br>
--------[Response](#response)<br>
--------[Middle Ware](#middle-ware)<br>
[Database Model](#database-model)<br>




       




## Installation

Create your project directory and initialize it as git directory but running this command in that dir.

```bash
$ git init
```

Pull Path's source to the directory you created with: 

```bash
$ git pull https://github.com/Wharley01/Path.git
```

If you are trying to download Path into an already existing git folder with unrelated history use:

```bash
$ git pull http://github.com/Wharley01/Path.git --allow-unrelated-histories
```

## Folder Structure

These are the folders you probably would be concerned with (unless you planning to contribute to Path's core development)

-<b>your-project-folder</b><br>
----<strong>path</strong> (your App's root folder) <br>
...<br>
-------Commands<br>
-------Controllers<br>
----------Live<br>
----------Route<br>
-------Database<br>
----------Migration<br>
----------Models<br>
-------Http<br>
----------MiddleWares<br>
...
#### Explanation

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

 
 You initiate the use of router
 ```php
use Path\Http\Router;

$router = new Router();
 ```
 
 proceed to listening to a request
```php
 use Path\Http\Router;

 $router = new Router();
 
 $router->get("/your/custom/route",function(){
     //do something here
  });
```
The code above does two things; the first is to listen for `GET` request to `/your/custom/route`(i.e., http://yourproj.dev/your/custom/route) while the second is to execute a particular `function`.


Path can also match dynamic url as seen below
```php
 use Path\Http\Router;

 $router = new Router();
 
 $router->get("/user/@id/profile",function(){
     //do something here
  });
```
Regular expressions is also a valid in url in the format shown below
```php
 use Path\Http\Router;

 $router = new Router();
 
 $router->get("/user/@id:[\d]+/profile",function(){
     //do something here
  });
```
There can be multiple routers listening to different routes with one `Router` object as shown below.

The `Router` object is instantiated once as `$router` and used to listen to more than one request.

```php
 use Path\Http\Router;
   
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
   NOTE: All routes codes must be written in `path/Routes.php`
   ````
   Now go on and visit http://yourproject.dev/your/custom/url you will get a json response


The second argument of `$router->request_method()` may also be a `class` name. In this case, the `class` must have an accessible method/function `response` which 
serves as an entry point to your class after it has been instantiated.

   ```php
 use Path\Http\Request;

 $router = new Router();
 
  $router->get("/your/custom/route", RouteAction::class);
  ```
   
And the `class` may look like this

   ```php
    class RouteAction{
        ...
        public function response (Request $request, Response $response) {
            // your awesome stuff goes here
        }
        ...
    }
   ```

We can also listen for any type of request (i.e: GET/POST/PUT e.t.c) using the `Route::any` function. The usage of this function can be extended to automatically run a certain block of code depending on `$_SERVER['REQUEST_METHOD']`(Request type).

This can be achieved by extending the `abstract Controller class`. An example is shown below 

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
    ...

}

```

The Controller Class Above can be used as shown below

```php
$router->any('/route/my/route', RequestHandler::class)
```

The method in class `RequestHandler` that will serve as response for `'/route/my/route'` depends on `$_SERVER['REQUEST_METHOD']`, for example:

if a GET request is sent to `'/route/my/route'` the `public function onGet(){}` serves as the response. a `RouterException` will be thrown if required method for the user's current  `request method` is not found  in your `Controller` Class 


NOTE: Using a custom class which extends the `abstract` `Controller` `class` on other route methods such as `$route->post()` and `$route->get()` will work fine, but it will only execute the function code that matches the REQUEST, Hence, this approach is not ideal.

However, It is a valid use case to use a class that's not extending `Controller abstract class`. In this scenario, Path will default to executing the response method in the Supplied Class. A `RouterException` is thrown if Path cannot find a response method in the provided class.

   
### Request
To interact with all request properties such as headers, url path properties, request types etc. Path is bundled with  a `class Request`. 

An instance of this class is instantiated on every request and passed as an argument to our function call in the `Router` request listening methods.
Hence, our previous definitions may be redefined as follows to enable access to the `Request` instance created.

```php
 use Path\Http\Request;
 use Path\Http\Router;
   
  ...
   
  $router->get("/your/custom/route",function(Request $request){
     //do something here or use $request here
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

...Or a data sent via html form


```php
  ...
   $request->fetch("input_name");
  ...
```  

 Files sent via form can be retrieved using the `$request->file()` method.
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
   Note that we used `$response->json()` method to return a json response in our callback function, which will execute when our route(`/your/custom/path`) is requested, there are many other available methods in `Response $response` as listed below:

   1. json( array $array, $status = 200 )
   2. text( string $text, $status = 200 )
   3. htmlString( string $html, $status = 200 )
   4. html( string $file_path, $status = 200 )
   5. redirect( string $route, $status = 200 )
   
   Methods that can be chained with the response method
      
   1. addHeader(array $headers)

### Middle Ware

The MiddleWare Concept was designed to add restriction(s) to accessibility of a particular route based on your preferred condition when needed, this can be very handy in cases like a restriction of API usage for the non-logged user, API-key system, request form validation and many more.

A middleware, when specified is first executed and its conditions checked before Path would decide whether to proceed with the request of fallback to the middleWare's fallback method.

##### The MiddleWare Class

In Path, MiddleWares must me created as a class in `path/Http/MiddleWares/` folder and your class must implement `Path\Http\MiddleWare`, a typical middleware class Looks like this:

```php
namespace Path\Http\MiddleWare;


use Path\Http\MiddleWare;
use Path\Http\Request;
use Path\Http\Response;
use Path\Storage\Sessions;

class isProd implements MiddleWare
{


    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @throws \Path\ConfigException
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
NOTE: In Path, Anywhere you are required to create a class, your `File` name must be the same as your `Class`'s name and each `Class` must be in separate file.
```

The code above describes two things for the router, 

```php
public function validate(Request $request, Response $response):bool
```
1. The `validate()` method returns a `Boolean` which will be checked by the router(When a user tries to request it), if the returned `Boolean` is `True` the router proceeds with the request.

```php
 public function fallBack(Request $request, Response $response):?Response
    
```
2. if the validate() method's returned value is `False`, the router uses `fallBack()` method of your middleWare class as the route's response.


#### Using the MiddleWare with Route

After creating your middleWare class, the next thing to do is use the middleware with any of your preferred route as described below 

```php
...
use Path\Http\MiddleWare\isProd;

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
use Path\Http\MiddleWare\IsValidPost;
use Path\Http\MiddleWare\IsLoggedUser;

$router->get([
   "path" => "/your/awesome/route",
   "middleware" => [
      IsLoggedUser::class,
      IsValidPost::class
   ]//multiple middleWares are placed in array
   ],function(){
// this will execute if the middleware "isProd"'s validate() method returns true

   })
...
```

MiddleWares validation is done in order of how you place them in the array and appropriate response is sent to the user based on the currently failing(returning false) MiddleWare, Proceeds to other when current one passes(returns true).

# Database Model
What's the essence of an App without a Database? Useless? Well,maybe not all the time 😄, but either way, Path has a very flexible mechanism designed for you to interact with your Web App's database, The essence of Database Model it to give you the ability to configure how you want each table to be interacted with.

 In Path Every Table in your database must be represented with a Class (Called Model) which extends the `abstract Class Path\Database\Model` which you will have to override its properties to suit your use case, a single database table can have multiple Database Model(if you want different set of rules for same table). All database model must be created in `path/Database/Models` folder.

A typical Database table model looks like this:

```php
<?php
/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Database\Models;


use Path\Database\Model;

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
##### Explanation

1. `protected $table_name        = "your_table_name";` specifies database table for this model.

2. `protected $non_writable_cols = ["id"];` specifies the which column can not be changed(not writable)

3. `protected $non_readable_cols = [];` specifies which columns cannot be read(would be filtered out if you try to read/fetch them)

There are more model configurations which will be listed in the next sub-section

#### Model Configuration reference

| Properties | Default Value  | Description
| :----------| ------------- | :--------------------  
|$primary_key | `id`  | Specifies the Primary of your model's table(Defaul)
|$table_name  | null  | Holds Model table name
|$record_per_page  | 10  | Total number of rows to return per page 
|$non_writable_cols | []   | The columns that can not be changed(not writable)
|$non_readable_cols | []  |  The columns that cannot be read(would be filtered out if you try to read/fetch them)
|$created_col | `date_added`  | Specifies the column that holds your timestamp when a new data was inserted
|$updated_col  | `last_update_date`  | Specifies the table column name that holds the timestamp of when last the row updates
|$fetch_method  | `FETCH_ASSOC`  | PDO Method to use in fetch result


### Database Model usage

After configuring your database Model, you can go on using your Model instantiating it, this way your have access to all the parent class `Path\Database\Model`'s objects.

#### Fetching Data

Below is an example of fetching data in database.

```php
...
use Path\Database\Models;

import(
    "path/Database/Models/Test"//import needed model
);

// instantiate your model
// and fetch  all data from your `your_table_name` as json response
$test1 = (new Models\Test())
         ->all();

// fetch a particular set of column instead

$test2 = (new Models\Test())
         ->select(['name','age']);//select just age and name

var_dump($test2->getAll());//return array of all data

var_dump($test2->getFirst());//return the first record only

var_dump($test2->getLast());//return the last row only

// fetch data by ID

...
```

##### Code explanation

1. `(new Models\Test())` instantiate your database model.

2. `->select(['name','age'])` specifies the columns you are interested in in this Model instance.

below them are demonstrated ways to fetch the result

1. `->getAll()` returns multi rows index based array of the data.

2. `->getFirst()` returns first single record in an associative array.

3. `->getLast()` returns last single record in an associative array.


#### Adding constraint Clause

Because you probably won't want to fetch all data in your database table all the time, you can use available constraint methods to your advantage.

Below is an example of using a constraint clause while fetching data

```php
use Path\Database\Models;

import(
    "path/Database/Models/Test"//import needed model
);

$fetch_data = (new Models/Test)
               ->select(['name'])//fetch only name column
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

#### constraint class  reference

|  Clause | Possible values | Description        | Example
| :------ | :---------------| :------------------ | :----
| `where(mixed $condition)`  |  column name, condition (i.e: age > 12) or an associative array(i.e: ['name' => 'adewale']) | This defines the WHERE clause to filter records | 1. `where("column")->like("%a")` <br><br>2.`where("column")->notLike("e%")` <br><br> 3.`where("column")->between(10,20)`<br><br>4.`where("age > 20")`<br><br>5. `where(["name" => "John Doe"])`
|`orWhere`|  column name, condition (i.e: age > 12) or an associative array(i.e: ['name' => 'adewale']) | Define Alternative condition, equivalent to SQL's "`OR WHERE `" clause| `where("score > 50")->orWhere("isPassed")`
| `like(String $wild_card)` | All SQL wildcard syntax (i.e: %value%) | Describes the LIKE wild card, equivalent to SQL's "`WHERE column LIKE '%value'`", `like()` method should always be combined with `where()` method which will specify the column in this context | `where("column_name")->like("%a")` 
| `notLike(string $wild_card)`| All SQL wildcard syntax (i.e: %value%) | Describes the `NOT LIKE` wild card, equivalent to SQL's "WHERE column NOT LIKE '%value'", like() method should always be combined with where() method which will specify the column in this context|where("column_name")->notLike("%a")
|



# Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

# License
[MIT](https://choosealicense.com/licenses/mit/)
