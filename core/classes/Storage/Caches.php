<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/18/2019
 * @Time 6:48 PM
 * @Project Path
 */

namespace Path\Storage;


class Caches
{
    private const CACHE_DIR = "path/.Storage/.Caches/";

    /**
     *
     * @param $key
     * @return null|string
     */
    static public function get($key):?string{
//        TODO: Do some validation for the key
        $caches_path = root_path().self::CACHE_DIR.$key.".pch";
        $value = @file_get_contents($caches_path);
        return  $value ? $value : null;
    }

    /**
     *
     * @param $key
     * @param $value
     * @return bool|int
     */
    static public function set($key, $value){
        $caches_path = root_path().self::CACHE_DIR.$key.".pch";
        return file_put_contents($caches_path,$value);
    }

    /**
     * @param $key
     */
    static public function delete($key){
        $caches_path = root_path().self::CACHE_DIR.$key.".pch";
        @unlink($caches_path);
    }
}