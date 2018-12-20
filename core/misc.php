<?php

use Path\PathException;

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


/**
 * @param mixed $classes
 * @param string $from
 * @throws PathException
 */
function load_class($classes, $from = "classes"){

    if(!is_array($classes)){
        $classes = preg_replace("/\.php$/","",trim($classes));
        $path = __DIR__.DIRECTORY_SEPARATOR.$from.DIRECTORY_SEPARATOR."{$classes}.php";
        if(!file_exists($path))
            throw new PathException("Class {$classes} not found; Path: {$path}");
        /** @var String $path */
        include_once "{$path}";
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