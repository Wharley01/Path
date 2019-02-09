<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 2/8/2019
 * @Time 11:31 PM
 * @Project macroware-vue
 */

namespace Path;


class SSEWatcher
{
    private $watching;
    private $params;
    public function __construct($watching,$params)
    {
        $this->watching = explode(",",$watching);
        $this->params = self::generateParams($params);
        var_dump($this->params);
    }

    private static function generateParams($params):array {
        preg_match("/\[([^\[^\]]+)\]/i",$params,$matches);
        if(!isset($matches[1])){
            return [];
        }
        $res = [];
        $params = explode(",",$matches[1]);
        foreach ($params as $param){
            preg_match("/([^=]+)=([^=]+)/i",$param,$val);
            $key = $val[1];
            $val = $val[2];
            $res[$key] = $val;
        }

        return $res;
    }


}