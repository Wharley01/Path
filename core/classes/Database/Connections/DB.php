<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 4/13/2019
 * @Time 7:48 PM
 * @Project path
 */

namespace Path\Database;


interface Database
{

    public function __construct();
    public static function close();

}