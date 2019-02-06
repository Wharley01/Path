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
    private static $session_id;

    public static function start($session_key){
        if(!preg_match("/^[-,a-zA-Z0-9]{1,128}$/",$session_key))
            throw new \Exception("Invalid Session ID");
        if(session_id() != ''){
            session_write_close();
        }

        session_name($session_key);
        session_start();
    }
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