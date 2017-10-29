<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
$age = 12;
$db->
select("Name")
    ->from("test_table")
    ->where(['id' => 1])
    ->where('id = 2');

print_r($db->getResult(false));


?>
