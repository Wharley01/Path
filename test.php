<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
$age = 12;
$db->Update('test')->Set('Name = wale')->Set(['School'=>'Unilag'])->Where(['Name'=>'Wale'])->orWhere('ID = 3');
try{
    $db->Exe();
}catch (Exception $exception){
    echo $exception->getMessage();
}


print_r($db->query_data['bind_data'])


?>
