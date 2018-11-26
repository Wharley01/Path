<?php

use Path\Model;
use Path\FileSys;
require_once "core/kernel.php";
load_class("Database/Models/User.php");
load_class("FileSys");
try{

$user = (new Model\User)
    ->where(['ID' => 5])
    ->select("Name")
    ->rightJoin("test2",["test2.col" => "test_table.col"])
    ->as("UserName");
//
var_dump(
    (new Model\User)
        ->select("Age")
        ->select($user)
        ->as("Users")
        ->where("ID = 9")
        ->leftJoin("test",["test.col" => "test_table.col"])
        ->get()
);

    var_dump(
        (new Model\User)
            ->select("Name")
            ->select("MATCH(artist) AGAINST(? IN BOOLEAN MODE)",["hello"])
            ->as("artist_relevance")
            ->where(["ID" => 6])
            ->whereCols("Name","Age")
            ->matches("hello")
            ->batch(0,10)
            ->sortBy("((title_relevance * 3) + artist_relevance)")
            ->get()
    );

}catch (\Path\DatabaseException $e){
    echo $e->getMessage();
}

