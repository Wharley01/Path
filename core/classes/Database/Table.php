<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 3/11/2019
 * @Time 2:29 AM
 * @Project path
 */

namespace Path\Core\Database;


interface Table
{
    public function install(Structure &$table);
    public function uninstall();
    public function populate(Model $table);
    public function update(Structure &$table);
}
