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
<?php

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
<?php


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