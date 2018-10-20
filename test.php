<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
echo "\n";
//Select Data
// $db->Update('test_table')
//     ->Set(['Name' => 'semite','Age' => 45])
//     ->Where(['ID' => 3])
//     ->Exe();


$db->Insert(['Name' => 'Adewale','Age' => '35'])
   ->Into('test_table')
   ->Exe();


$select = $db->Select('*')
             ->From('test_table')
             ->orderBy(['Name' => 'ASC'])
             ->Get();

print_r($select);//Print result
?>
