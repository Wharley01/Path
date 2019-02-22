<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/17/2019
 * @Time 1:23 AM
 * @Project Path
 */

namespace Path\Controller\Live;


use Path\Storage\Caches;
use Path\Http\Response;
use Path\Http\Watcher;
use Path\LiveController;
use Path\Storage\Sessions;

class TestLive implements LiveController
{
    // this array of methods that can be watched
    // the array key here can either represent a method of dynamic data being set in constructor
    public $isLogin = false;
    public $profile = 0;
    //every time the watcher checks this Live Controller, it passes some data to it 
    public function __construct(
        Watcher  &$watcher,
        Sessions $sessions,//the session instance that can be used for auth. with the client side
        $message//message sent from User(client Side)
    )
    {
        // to avoid executing every methods every time in the Watcher server(Websocket),
        
        /*
        *  
        * you should set the value of each key(which represents methods in this class)
        * to something that changes based on the return value
        * of the method they represents
        *
         */
//        if($watcher->)
        $watcher->sendMessage("hello world new.....","profile");

        $this->isLogin =  "hellodd000";

        $this->profile = [
            "name" => "Adewalesss",
            "this" => "should zzzzssworkddd"
        ];

    }

    public function profile(
        Response $response,
        Watcher  &$watcher,
        Sessions $sessions,
        ?String  $message
    ){

        return $response->json($this->profile);
    }
}