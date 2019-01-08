<?php
/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Database\Models;


use Data\Model;

class User extends Model
{
    protected $table_name               = "user";
    protected $non_writable_cols        = ["id"];
    protected $readable_cols            = ["id","name","description"];

    public function __construct()
    {
        parent::__construct();
    }
}