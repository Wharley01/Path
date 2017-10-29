<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
$age = 12;
$db->
select("name")
    ->from("employees")
    ->where('name > wale')
    ->or_where(['school' => 'test'])
    ->where(['do' => 'sleep'])
    ->where(['address' => 'ILOBU','food' => 'eba'])
    ->or_where('status = alive')
    ->or_where(['school'=>'mahmud']);

print_r($db->query_data);


?>
