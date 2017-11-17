<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
$age = 12;
$db->Insert('Name = wale,Age = 12')->Into('test_table');
try{
    $db->Exe();
}catch (Exception $exception){
    echo $exception->getMessage();
}


print_r($db->query_data['bind_data'])


?>
