<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
$age = 12;
$db->
select("Name")
    ->from("test_table")
    ->Where(['id' => 1])
    ->orWhere('id = 2');

print_r($db->Get(false));


?>
