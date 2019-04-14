<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 7/15/2018
 * Time: 12:28 PM
 */

namespace Path\Core\Misc;



class Utilities
{
    public function __construct()
    { }
    static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return strtoupper($randomString);
    }

    /**
     * @param array $array
     */
    static function Log(array $array)
    { }
}
