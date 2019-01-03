<?php

use Path\Database;
use Path\Database\Prototype;
use Path\FileSys;
use Path\Database\Structure;

require_once "Core/kernel.php";
//load_class("Database/Models/User.php");
load_class("Database/Prototype");
//load_class("FileSys");

try{

    $prototype = new Prototype();

   $prototype->alter("user",function (Structure $user){

       $user->column("goe_loc")
       ->type("INT")
       ->default('20')->update();

   });

    $prototype->create("user",function (Structure $user){

        $user->column('id')
            ->type("INT")
            ->increments()
            ->primaryKey();

        $user->column("goe_loc")
            ->type("INT")
            ->default('20')
            ->uniqueKey();

        $user->uniqueKey("address","school");

    });

    $prototype->drop("doctor");

//    $table_structure = new Structure("user");



//    $table_structure->rename('full_name')
//        ->to("full_name")
//        ->type("TEXT");
//
//    $table_structure->column("goe_loc")
//        ->type("INT")
//        ->default('20')
//        ->update();

//    $table_structure->getRawQuery();

//$user = (new Model\User)
//        ->where(['ID' => 5])
//        ->select("Name")
//        ->rightJoin("test2",["test2.col" => "test_table.col"])
//        ->as("UserName");
////
//var_dump(
//    (new Model\User)
//        ->select("Age")
//        ->select(
//            $user
//        )
//        ->as("Users")
//        ->where("ID = 9")
//        ->leftJoin("test",["test.col" => "test_table.col"])
//        ->get()
//);
//
//var_dump(
//        (new Model\User)
//        ->select("Name")
//        ->select("MATCH(artist) AGAINST(? IN BOOLEAN MODE)",["hello"])
//        ->as("artist_relevance")
//        ->where(["ID" => 6])
//        ->whereCols("Name","Age")
//        ->matches("hello")
//        ->batch(0,10)
//        ->sortBy("((title_relevance * 3) + artist_relevance)")
//        ->get()
//    );

}catch (\Path\DataStructureException $e){
    echo $e->getMessage().PHP_EOL;
    echo $e->getTraceAsString();
}

