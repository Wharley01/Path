<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/29/2018
 * @Time 7:21 AM
 * @Project Path
 */

namespace Path\Storage;


class Cookies
{
    public const ONE_DAY = 86400;
    public const ONE_WEEK = 604800;

    public static function store(
        $key,
        $value,
        $expire = null,
        $path = "/",
        $domain = "",
        $secure = false,
        $httponly = false
){
        if(is_null($expire))
            $expire = time() + self::ONE_DAY;

        setcookie($key,$value,$expire,$path,$domain,$secure,$httponly);

    }
    public static function change(
        $key,
        $new_value,
        $expire = null,
        $path = "/",
        $domain = "",
        $secure = false,
        $httponly = false
    ){
            if(isset($_COOKIE[$key])){
                self::store($key,$new_value,$expire,$path,$domain,$secure,$httponly);
            }
    }
    public static function delete($key){
            self::change($key,"",time() - 3600);
    }

    public static function all():array {
        return $_COOKIE;
    }

    public static function get($key){
        return $_COOKIE[$key];
    }
    public function isEnabled():bool {
        self::store("path_12__cookie_test","test",3600);
        if(isset($_COOKIE['path_12__cookie_test'])){
            self::delete('path_12__cookie_test');
            return true;
        }else{
            return false;
        }
    }
    public static function exists($key){
        return isset($_COOKIE[$key]);
    }
    public static function days(int $days):int {
        return ($days * 86400);
    }
    public static function weeks(int $weeks):int{
        return ($weeks * 604800);
    }
    public static function months(int $months):int{
        return ($months * 2.628e+6);
    }


}