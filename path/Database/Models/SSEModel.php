<?php
/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Database\Models;


use Data\Model;

class SSEModel extends Model
{
    protected $table_name               = "ssemodel";
    protected $non_writable_cols        = ["id"];
    protected $readable_cols            = ["id","name","description"];

    public function __construct()
    {
        parent::__construct();
    }
}