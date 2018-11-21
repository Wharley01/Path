<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 11/18/2018
 * Time: 2:07 AM
 */

namespace Path\Model;
load_class("Database/Model");

use Data\Model;


class User extends Model
{
    protected $table_name           = "test_table";

    protected $non_writable_cols    = ['name'];
    protected $readable_cols        = ['Name','Age'];

    protected $fetch_method         = "FETCH_ASSOC";
    protected $primary_key          = "ID";

    public function __construct()
    {
        parent::__construct();
    }
}