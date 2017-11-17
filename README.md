# db-queryBuilder-helper

A simple, lightweight, versatile and secure Sql query builder.
Query builder is powered by PHP PDO, All parameters are bond during execution. 

##Get Started

```
$db = new Database(new Mysql());`

$select = $db->Select('Column')->From('Table_Name')->Where(\['ID'=>'1']);
//This is the simplest form of a select statement
```

The code above will build query:
```
SELECT Column FROM Table_Name WHERE ID = 1
```


####Documentation

#####Selecting Data from database
```
$select = $db->Select('Column')//The column to select, Put * for all
             ->From('Table_Name')//The Table to Select from
             ->Where(\['ID'=>'1']);//Condition
```
Example Explained

`Where()` Method Accept Either Array of Conditions, Or String, For Example the `Where(\['ID'=>'1'])` Method above can be replaced with `Where('ID = 1')` or `Where('ID > 1')`


`Select()` Method can accept multiple Columns separated with comma, For Example `Select('Name,Age')`.
 
 You can use SQL functions, for example `Select('AVG(Age)')`.
 
 `Select()` Method Can also be chained with `_As()` Method, For Example `Select('AVG(Age)')->_As('AverageAge')->From('Table_Name')`


##Continuing...