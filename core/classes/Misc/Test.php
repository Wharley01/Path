<?php


namespace Path\Core\Misc;



class Test
{
    public function __construct()
    { }
    public static function is_same($arr1, $arr2)
    {
        return trim(json_encode($arr1)) == trim(json_encode($arr2));
    }
}
