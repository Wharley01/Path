<?php
use Path\Core\Error\Exceptions;

session_start();
define("ROOT_PATH", preg_replace("/core$/i", "", __DIR__));

spl_autoload_register(function ($class_name) {
    $break = explode("\\", $class_name);
    $root = $break[0];
    $type = $break[1];
    $file_path = null;
    $throw_error = false;

    switch ($type){
        case "Core":
            $file_path = "core/classes/".join("/",array_slice($break,2));
            break;
        case "App":
            $file_path = "path/".join("/",array_slice($break,2));
            $throw_error = true;
            break;
        case "Plugins":
            $folder = array_slice($break,2);
            $class = array_pop($folder);
            $folder = join("/",$folder);
            $file_path = 'core/Plugins/'.$folder.'/src/'.$class;
//            echo $file_path;
            break;
    }
    if($file_path !== null){
        $_file_path = ROOT_PATH . $file_path . ".php";
        if (!is_readable($_file_path)) {
            if($throw_error)
                throw new Exceptions\Path("File {$_file_path} does not exist");

            echo "Warning[x]:  File {$_file_path} does not exist";
        } else {
            import($file_path);
        }
    }

});




require_once __DIR__ . DIRECTORY_SEPARATOR . "misc.php";
