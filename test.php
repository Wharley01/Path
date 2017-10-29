<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
$age = 12;
$db->
select("*")
    ->from("test_table");

print_r($db->getResult(false));


?>
