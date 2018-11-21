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