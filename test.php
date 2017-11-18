<?php
include_once "core/kernel.php";
use Connection\Mysql;
use Data\Database;

$db = new Database(new Mysql());
//Select Data
$select = $db->Select('Name')->From('test_table')->Where(['ID'=>1])->Get();
print_r($select);//Print result


//Update
 $db->Update('test_table')
     ->Set(['Name'=>'Sulaimen'])
     ->Where('Name')
     ->Like('wale')
     ->Where('ID = 3');
//Try executing the query
 try{
     $db->Exe();//OR ->Execute();
 }catch (Exception $e){
     echo $e->getMessage();
 }

//Insert
$db->Insert(['Name'=>'Sulaimen','Age' => '34'])
    ->Into('test_table');
//Try executing the query
try{
    $db->Exe();//OR ->Execute();
}catch (Exception $e){
    echo $e->getMessage();
}

//DELETE
$db->deleteFrom('test_table')
    ->Where('Name = wale')
    ->Execute();


?>
