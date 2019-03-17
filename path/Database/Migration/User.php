<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 3/11/2019
 * @Time 1:05 AM
 * @Project path
 */

namespace Path\Database\Migration;


use Path\Database\Model;
use Path\Database\Structure;
use Path\Database\Table;

class User implements Table
{
    public $table_name = "user";
    public function install(Structure &$table)
    {
        // TODO: Implement install() method.
    }

    public function uninstall()
    {
        // TODO: Implement uninstall() method.
    }

    public function populate(Model $table)
    {
        // TODO: Implement populate() method.
    }

    public function update(Structure &$table)
    {
        // TODO: Implement update() method.
    }
}