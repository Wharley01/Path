<?php

use Path\Core\Error\Exceptions;

/**
 * get root path
 * @return mixed
 */
function root_path()
{
    return preg_replace("/Core$/i", "", __DIR__);
}

/**
 * @param $key
 * @return mixed
 * @throws Exceptions\Config
 */
function config($key)
{
    $root_path = root_path() . "path" . DIRECTORY_SEPARATOR . "project.pconf.json";
    if (!file_exists($root_path))
        throw new Exceptions\Config("config file \"{$root_path}\" not found");

    if (!$configs = json_decode(file_get_contents($root_path), true))
        throw new Exceptions\Config("unable to parse json in \"{$root_path}\", please check the syntax docs for json");

    $key = explode("->", $key);
    $root = $configs[$key[0]];
    for ($i = 1; $i < count($key); $i++) {
        $root = @$root[$key[$i]];
    }
    return $root;
}


/**
 * @param mixed $classes
 * @throws Exceptions\Config
 * @throws Exceptions\Path
 */

function import(...$classes)
{
    $path = root_path();
    foreach ($classes as $class) {
        if (!file_exists($path))
            throw new Exceptions\Config("Set Project directory Appropriately in \"core/config.ini\" ->  " . getcwd());
        $_class = preg_replace("/\.php$/", "", trim($class));

        $_class = $path . DIRECTORY_SEPARATOR . $_class . ".php";

        if (!file_exists($_class)) {
            debug_print_backtrace();
            throw new Exceptions\Path("Class \"{$class}\" not found in \"{$_class}\"");
        }
        //        echo $_class;
        require_once $_class;
    }
}

function get_cli_args(array $listening, array $args)
{
    array_shift($args); //remove the first one
    $res = [];
    for ($i = 0; $i < count($args); $i++) {
        $arg = trim($args[$i]);
        if (in_array($arg, $listening)) {
            //            found a listening
            //            get the next arg
            if (in_array(@$args[$i + 1], $listening)) {
                $res[$arg] = null;
            } else {
                $res[$arg] = @$args[$i + 1] ?? null;
            }
        }
    }
    return $res;
}

function get_full_path($file_path)
{
    $path = $_SERVER['DOCUMENT_ROOT'];
    return $path . $file_path;
}

function treat_path($path)
{
    return (strripos($path, "/") == (strlen($path) - 1)) ? $path : $path . "/";
}
