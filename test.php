<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());

$select = $db->Select('Name')
             ->From('test_table')
             ->Where('Name')
             ->Like('Ade')
             ->orWhere('Name')
             ->Like('Sul')
             ->Get();

print_r($select);

//try{
//    $db->Exe();
//}catch (Exception $exception){
//    echo $exception->getMessage();
//}





?>
