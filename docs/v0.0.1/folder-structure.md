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
