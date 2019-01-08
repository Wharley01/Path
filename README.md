# PHP Path

Path is an API-first PHP framework built with javascript in mind

## Contents

[Installation](#Installation) <br>
[Folder Structure](#Folder-Structure)
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
## Your First API

## Usage

```php
//code
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)