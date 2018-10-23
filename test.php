<?php
include_once "core/kernel.php";
$class_  = $argv[1];
$method_ = $argv[2];
load_class($class_);

$class_ = "\Path\\".$class_;
$test = new $class_();

var_dump($test->$method_());


