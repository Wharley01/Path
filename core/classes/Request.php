<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 7/14/2018
 * Time: 4:42 AM
 */

namespace Airdrop;


class Request
{
    public $request_type;
    public $server;
    public function __construct(){
        $this->request_type = $_SERVER["REQUEST_METHOD"];
        $obj = new \stdClass();
        foreach ($_SERVER as $key => $value){
            $obj->$key = $value;
        }
        $this->server = $obj;
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