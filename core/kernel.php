<?php

use Path\PathException;
require_once "classes/Exceptions.php";

/**
 * @param $classes
 * @param string $from
 * @throws PathException
 */
require_once __DIR__.DIRECTORY_SEPARATOR."misc.php";
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