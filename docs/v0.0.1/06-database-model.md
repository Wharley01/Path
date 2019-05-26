## Database Model

What's the essence of an App without a Database? Useless? Well, maybe not all the time 😄, but either way, Path has a very flexible mechanism designed for you to interact with your Web App's database, The essence of Database Model it to give you the ability to configure how you want each table to be interacted with, like restricting column from being updated, setting columns that can be fetched and so on.

In Path Every Table in your database must be represented with a  `Class (Called Model)` which extends the `abstract Class Path\Core\Database\Model` which you will have to override its properties to suit your use case, a single database table can have multiple Database Model(if you want different set of rules for the same table). All database model must be created in `path/Database/Models` folder or its sub-folder.

A typical Database table model looks like this:

```php
<?php


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
<?php

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
<?php

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
<?php

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
<?php

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
<?php

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
<?php

use Path\Core\Database\Models;

//this will update json content of the profile column
$update = (new Models/Test)
            ->update([
               "profile->name" => "Adewale"
               ]);

```

##### Referencing JSON object key's value in WHERE clause

```php
<?php


use Path\Core\Database\Models;
//using json key's valuein Where clause

$select = (new Models/Test)
            ->where('profile->age > 18')
            ->orWhere([
               'profile->gender' => 'female'
               ])
            ->getFirst();

```