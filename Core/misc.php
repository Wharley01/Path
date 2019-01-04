<?php

use Path\PathException;

/**
 * @param $key
 * @return mixed
 */
function config($key)
{
    $root_path = preg_replace("/Core$/","",__DIR__);
    $configs = parse_ini_file($root_path."Path".DIRECTORY_SEPARATOR."config.ini", true);

    $key = explode("->",$key);

    $root = $configs[$key[0]];
    for($i = 1;$i < count($key);$i++){
        $root = @$root[$key[$i]];
    }
    return $root;
}


/**
 * @param mixed $classes
 * @param string $from
 * @throws PathException
 */
function load_class($classes){

    if(!is_array($classes)){
        $classes = preg_replace("/\.php$/","",trim($classes));
        $path = __DIR__ . DIRECTORY_SEPARATOR .DIRECTORY_SEPARATOR."{$classes}.php";
        if(!file_exists($path))
            throw new PathException("Class {$classes} not found; Path: {$path}");
        /** @var String $path */
        include_once "{$path}";
    }else{
        foreach ($classes as $class) {
            $class = preg_replace("/\.php$/","",trim($class));
            $path = __DIR__ . DIRECTORY_SEPARATOR .DIRECTORY_SEPARATOR."{$class}.php";
            if(!file_exists($path))
                throw new PathException("Class {$class} not found; Path: {$path}");
            else
                include_once $path;
        }
    }

}

function import(...$classes){
    foreach ($classes as $class){
        $path = $_SERVER['DOCUMENT_ROOT'].config("PROJECT->directory");
        if(!file_exists($path))
            throw new \Path\ConfigException("Set Project directory Appropriately in \"core/config.ini\" ->  ".getcwd());
        $_class = preg_replace("/\.php$/","",trim($class));
        $_class = $path.DIRECTORY_SEPARATOR.$class.".php";

        if(!file_exists($_class)){
            throw new PathException("Class \"{$class}\" not found in \"{$_class}\"");
        }
        require_once $_class;
    }
}

function get_cli_args(array $listening,array $args){
    array_shift($args);//remove the first one
    $res = [];
    for($i = 0;$i < count($args); $i ++){
        $arg = trim($args[$i]);
        if(in_array($arg,$listening)){
//            found a listening
//            get the next arg
            if(in_array(@$args[$i + 1],$listening)){
                $res[$arg] = true;
            }else{
                $res[$arg] = @$args[$i + 1] ?? true;
            }

        }
    }
    return $res;
}

function get_full_path($file_path){
    $path = $_SERVER['DOCUMENT_ROOT'];
    return $path.$file_path;
}

function treat_path($path){
   return (strripos($path,"/") == (strlen($path) - 1))?$path:$path."/";
}