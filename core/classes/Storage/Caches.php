<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/18/2019
 * @Time 6:48 PM
 * @Project Path
 */

namespace Path\Core\Storage;


class Caches
{
    private const CACHE_DIR = "path/.Storage/.Caches/";

    /**
     *
     * @param $key
     * @param null $read_path
     * @return null|string
     */
    static public function get($key,$read_path = null): ?string
    {
        //        TODO: Do some validation for the key
        $caches_path = ($read_path ?? ROOT_PATH . self::CACHE_DIR) . $key . ".pch";
        $value = @file_get_contents($caches_path);
        return  $value ?? null;
    }

    /**
     *
     * @param $key
     * @param $value
     * @param null $write_path
     * @return bool|int
     */
    static public function set($key, $value, $write_path = null)
    {
        $caches_path = ($write_path ?? ROOT_PATH . self::CACHE_DIR) . $key . ".pch";
        return file_put_contents($caches_path, $value);
    }

    static public function cache($key, $value, $write_path = null)
    {
        $caches_path = ($write_path ?? ROOT_PATH . self::CACHE_DIR) . $key . ".pch";
        return file_put_contents($caches_path, $value);
    }

    /**
     * @param $key
     * @param null $write_path
     */
    static public function delete($key, $write_path = null)
    {
        $caches_path = ($write_path ?? ROOT_PATH . self::CACHE_DIR). $key . ".pch";
        @unlink($caches_path);
    }

    static function deleteAll(){
        $files = glob(($write_path ?? ROOT_PATH . self::CACHE_DIR).'*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }
    }
}
