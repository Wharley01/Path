<?php

use Path\Model;

require_once "core/kernel.php";
load_class("Database/Models/User.php");
try{
$user_model = (new Model\User())

            ->where("Name = Adewale");
//            ->where(['name' => "wale"])
//            ->where("total_books <> 10")
//            ->orWhere("age = 66")
//            ->orWhere("test = fuck");

   var_dump($user_model->all(["Name"]));

}catch (\Path\DatabaseException $e){
    echo $e->getMessage();
}

