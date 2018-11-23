<?php
include_once "core/kernel.php";
load_class(["Http/Request","Http/Response","Test"]);
use Path\Test;
$test_json  = trim(@$argv[1]);
$req = new \Path\Http\Request();
$res = new \Path\Http\Response();
if(!strlen($test_json)){
    $test_json = json_decode(file_get_contents("test.json"),true);
}
$passed = 0;
$failed = 0;

function recursive_test($test_json,$passed,$failed,&$req,&$res,$instance = null,$x){
    for($i = 0; $i < count($test_json);$i ++){
        $test_arr = (object)$test_json[$i];
        if(is_null($instance)){
            load_class($test_arr->controller,"controllers");
            $ini = "Path\Controller\\".$test_arr->controller;
            $req->params = (object) $test_arr->args['params'];
            $req->inputs = $test_arr->args['inputs'];
            $class_inst = new $ini();
            $ini = $class_inst->{$test_arr->method}($req,$res);
        }else{
            $class_inst = $instance;
            $ini = $class_inst->{$test_arr->method}($req,$res);
        }
       if($ini instanceof \Path\Http\Response){
            $result = $ini->content;
       }else{
           $result = $ini;
       }
        if(!Test::is_same(json_decode($result,true),$test_arr->exp_result)){
            $failed += 1;
            $x += 1;
            echo PHP_EOL.PHP_EOL."\033[1;31m +++++[Test {$x} Failed] \e[0m".PHP_EOL.PHP_EOL;
            echo "------ Controller:     ".$test_arr->controller.PHP_EOL;
            echo "------ Method:         ".$test_arr->method.PHP_EOL;
            echo "------ EXPECTED Value: ".json_encode($test_arr->exp_result).PHP_EOL;
            echo "------ RETURNED Value: ".$result.PHP_EOL;
            echo "--- TOTAL FAILED NOW: ".$failed.PHP_EOL;
        }else{
            $passed += 1;
            $x += 1;
            echo PHP_EOL.PHP_EOL."\033[1;32m +++++[Test {$x} Passed] \e[0m \n".PHP_EOL;
            echo "------ Controller:     ".@($test_arr->controller ?? "Undefined").PHP_EOL;
            echo "------ Method:         ".$test_arr->method.PHP_EOL;
            echo "------ EXPECTED Value: ".json_encode($test_arr->exp_result).PHP_EOL;
            echo "------ RETURNED Value: ".$result.PHP_EOL;
            echo "------ TOTAL PASSED NOW: ".$passed.PHP_EOL;
        }
        if(@$test_arr->chain){
            recursive_test([$test_arr->chain],$passed,$failed,$req,$res,$class_inst,$x);
        }
    }
}
recursive_test($test_json,$passed,$failed,$req,$res,null,0);


//echo "\e[1;32m Merry Christmas!\e[0m \n";


