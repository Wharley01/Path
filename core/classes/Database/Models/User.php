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
    protected $table_name = "user_table_edited_from_child_class";
    protected $forbidden_cols = ['name'];
    public function __construct()
    {
        parent::__construct();
    }




}