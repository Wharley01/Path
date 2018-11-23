<?php

/**
 * @param $key
 * @return mixed
 */
function config($key)
{
    $configs = parse_ini_file(__DIR__.DIRECTORY_SEPARATOR."config.ini", true);
    $key = explode("->",$key);

    $root = $configs[$key[0]];
    for($i = 1;$i < count($key);$i++){
        $root = @$root[$key[$i]];
    }
    return $root;
}

function load_class($classes,$from = "classes"){

    if(!is_array($classes)){
        $classes = preg_replace("/\.php$/","",trim($classes));
        $path = __DIR__.DIRECTORY_SEPARATOR.$from.DIRECTORY_SEPARATOR."{$classes}.php";
        if(!file_exists($path))
            throw new PathException("Class {$classes} not found; Path: {$path}");
        include_once $path;
    }else{
        foreach ($classes as $class) {
            $class = preg_replace("/\.php$/","",trim($class));
            $path = __DIR__.DIRECTORY_SEPARATOR.$from.DIRECTORY_SEPARATOR."{$class}.php";
            if(!file_exists($path))
                throw new PathException("Class {$class} not found; Path: {$path}");
            else
                include_once $path;
        }
    }

}