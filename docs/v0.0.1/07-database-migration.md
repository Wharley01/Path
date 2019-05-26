## Database migration

Managing database tables is stressful, having to import sql files on every installation is tiring and needs automation, for this reasons Path comes with a mechanism to automate your database installation called `Database Migration`, a Database migration file represents each of the tables in your applications database `(Note: this is different from Database Model)` and are saved in `path/Database/Migration`, in it are where table columns are described, a typical database migration file looks like this:

```php
<?php


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
<?php

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
<?php

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
