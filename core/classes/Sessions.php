<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/29/2018
 * @Time 7:21 AM
 * @Project Path
 */

namespace Path;


class Sessions
{
    public static function store($key,$value){
        $_SESSION[$key] = $value;
    }
    public static function get($key){
        return $_SESSION[$key] ?? null;
    }
    public static function delete($key){
        unset($_SESSION[$key]);
    }
}