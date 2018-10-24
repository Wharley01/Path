<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 7/14/2018
 * Time: 4:42 AM
 */

namespace Path\Http;


class Request
{
    public $METHOD;
    public $server;
    public function __construct(){
        $this->METHOD = $_SERVER["REQUEST_METHOD"];

        if(!@$_SERVER['REDIRECT_URL'])
            $_SERVER['REDIRECT_URL'] = "/";

        $this->server = (object)$_SERVER;
    }
    static function GET(){
    $obj = new \stdClass();
    foreach ($_GET as $key => $value){
        $obj->$key = htmlspecialchars(trim($value));
    }
    return $obj;
    }
    static function POST(){
        $obj = new \stdClass();
        foreach ($_POST as $key => $value){
            $obj->$key = $value;
        }
        return $obj;
    }

}