# db-queryBuilder-helper

A simple, lightweight, versatile and secure Sql query builder.
Query builder is powered by PHP PDO, All parameters are bond during execution. 

## Get Started

```
$db = new Database(new Mysql());

$select = $db->Select('Column')->From('Table_Name')->Where(['ID'=>'1'])->Get();
//This is the simplest form of a select statement
```

The code above will build query:
```
SELECT Column FROM Table_Name WHERE ID = 1
```
### Method reference

* Select()
* From()
* _As()
* Where()
* orWhere()
* Like()
* notLike()
* Between()
* notBetween()
* Update()
* Set()
* Insert()
* Into()
* deleteFrom()
* Exe() Alias of Execute()
* Get() 
* orderBy()
### Documentation

#### Selecting Data from database
```
$select = $db->Select('Column')//The column to select, Put * for all
             ->From('Table_Name')//The Table to Select from
             ->Where(['ID'=>'1'])->Get();//Condition
```
Example Explained

`Where()` Method Accept Either Array of Conditions, Or String, For Example the `Where(\['ID'=>'1'])` Method above can be replaced with `Where('ID = 1')` or `Where('ID > 1')`


`Select()` Method can accept multiple Columns separated with comma, For Example `Select('Name,Age')`.
 
 You can use SQL functions, for example `Select('AVG(Age)')`.
 
 `Select()` Method Can also be chained with `_As()` Method, For Example `Select('AVG(Age)')->_As('AverageAge')->From('Table_Name')`

To order your data, you can use orderBy() method, check out the exampl below
```
$db->Select('*')->From('test_table')->orderBy(['Name' => 'ASC'])->Get();
```
this example will select all from table "test_table" and order the result by the Name column in Ascending order


#### Updating Data

The code below can be used to update data in data
```
$db->Update('Table_Name')->Set(['Name' => 'Sulaiman Adewale','Age'=>'21'])->Where('ID = 2')->Exe();
```
The code above will generate
```
UPDATE Table_Name SET `Name` = 'Sulaiman Adewale',`Age` = '21' WHERE `ID` = 2
```
Code Explanation

The `Update()` Method accepts the table you are trying to update in string, the `Set()` Accepts the Data to update while the method `Where()` Can be chained to set the condition to check for before it can Update.

### Insert Data

Inserting data is similar to updating, the difference is you don't need to specify any condition.

Example
```
$db->Insert(['Name' => 'My other Name','Age'=>'4'])->Into->('Table_Name')->Exe();
```

Explanation

To insert data in database, you need two methods chained together, the `Insert()` method and the `Into()` method.

`Insert()` -- Accepts the data you are willing to insert in associative array or string separated with comma, Meaning `['Name' => 'My other Name','Age'=>'4']` can be replaced with `Name = My other Name,Age = 4`.

`Into()` -- Accepts the table Name, (the table to insert data to)

#### Deleting data

Deleting data is very straight forward. The code below illustrate that.

```
$db->deleteFrom('Table_Name')->Where(['ID'=>1]);//Can be chained with Where method, and other conditional statements
```

#### More on Conditional Where()

To select rows that match a specific search keyword
```
$select = $db->Select('Name')//Column to select
             ->From('Table_Name')
             ->Where('Name')//Column to match the keyword with
             ->Like('SearchKeyword')//the search keyword, you can use notLike() Here too
             ->Get();
```


