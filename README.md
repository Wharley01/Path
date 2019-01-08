# PHP Path

Path is an API-first PHP framework built with javascript in mind

## Contents

[Installation](#Installation) <br>
[Folder Structure](#Folder-Structure)



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
--- core\
------ \\...\
--- path\
------ Commands *<------ Contains All your custom Console Commands*\
------ Controllers *<------ Contains Your API Controller*\
------ Database \
--------- Models *<-------- Contains your Database Models*\
------------ \\...\
------ Http\
--------- MiddleWares *<-- Contains your Route MiddleWares*\
------------ \\...\
------ config.ini *<------ Your Configuration file*\
------ Routes.php *<------ Contains your routes*\

#### Folder structure In Image

![Image](./docs/images/folder-structure.jpg)



## Your First API
 you can listen to your preferred URL with Path's Router, for example:

 
 You initiate the use of router
 ```php
+ use Path\Http\Router;
+ $router = new Router();
 ```
 
 proceed to listening to a request
   ```php
   use Path\Http\Router;
   use Path\Http\Request;
   use Path\Http\Response;
   
   $router = new Router();
  
   $router->get(
        "/your/custom/url",//Route to listen to
       function(Request $request, Response $response){
   // call back function to fire when user visits 
   // http://yoursite.dev/your/custom/url
        return $request->json(['message' => 'this is my first route'],200);
   })
   ```
   
   In the code above, we are watching the route path `/your/custom/url` and we expect a callback function to execute when users requests that route. in the case of the above code, we are returning a json in our callback function.
   
   your call back function will be given two variables which is an instance of `Request` and `Response` 
   
   ````
   NOTE: All routes codes must be written in path/Routes.php
   ````
   Now go on and visit http://yourproject.dev/your/custom/url you will get a json response
 

## Usage

```php
//code
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)