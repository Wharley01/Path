<?php

use Path\PathException;
require_once "classes/Exceptions.php";

/**
 * @param $classes
 * @param string $type
 * @throws PathException
 */
function load_class($classes,$type = "classes"){
    if(!is_array($classes)){
        $path = __DIR__.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR."{$classes}.php";
        if(!file_exists($path))
            throw new PathException("Class {$classes} not found; Path: {$path}");
        include_once $path;
    }else{
        foreach ($classes as $class) {
            $path = __DIR__.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR."{$class}.php";
            if(!file_exists($path))
                throw new PathException("Class {$class} not found; Path: {$path}");
            else
                include_once $path;
        }
    }

}