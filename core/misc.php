<?php

use Path\PathException;

/**
 * get root path
 * @return mixed
 */
function root_path(){
    return preg_replace("/Core$/i","",__DIR__);
}

/**
 * @param $key
 * @return mixed
 * @throws \Path\ConfigException
 */
function config($key)
{
    $root_path = root_path()."Path".DIRECTORY_SEPARATOR."project.pconf.json";
    if(!file_exists($root_path))
        throw new \Path\ConfigException("config file \"{$root_path}\" not found");

    if(!$configs = json_decode(file_get_contents($root_path),true))
        throw new \Path\ConfigException("unable to parse json in \"{$root_path}\", please check the syntax docs for json");

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
        $path = __DIR__ . DIRECTORY_SEPARATOR."Classes".DIRECTORY_SEPARATOR."{$classes}.php";
        if(!file_exists($path))
            throw new PathException("Class {$classes} not found; Path: {$path}");
        /** @var String $path */
        include_once "{$path}";
    }else{
        foreach ($classes as $class) {
            $class = preg_replace("/\.php$/","",trim($class));
            $path = __DIR__ . DIRECTORY_SEPARATOR."Classes".DIRECTORY_SEPARATOR."{$class}.php";
            if(!file_exists($path))
                throw new PathException("Class {$class} not found; Path: {$path}");
            else
                include_once $path;
        }
    }

}

function import(...$classes){
    $path = root_path();
    foreach ($classes as $class){
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
