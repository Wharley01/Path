<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
$age = 12;
$db->select("name")->from("employees")->where(['name' => 'wale'])->where(['school' => 'test'])->where(['do' => 'sleep'])->or_where('type = test,another = test')->where('car = benz')->or_where(['address' => 'ILOBU','food' => 'eba']);

print_r($db->query_data);


?>
