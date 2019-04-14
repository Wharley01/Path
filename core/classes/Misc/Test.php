<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 11/5/2018
 * Time: 2:50 AM
 */

namespace Path;


class Test
{
    public static function is_same($arr1,$arr2){
        return trim(json_encode($arr1)) == trim(json_encode($arr2));
    }
}