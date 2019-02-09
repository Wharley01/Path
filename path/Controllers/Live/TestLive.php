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
        Sessions $sessions,//the session instance that can be used for auth. with the client side
        $params,//the params parsed from Javascript Path-Watcher
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
        $this->isLogin =  "hellodd000";

        /* this will emit changes anytime session value of 
        * is_logged_in changes(maybe when user logs out),
        * isLogin does not have a method representation so is_logged_in session value 
        * will ve received in the client side(js Path-Watcher) 
        */
        $this->profile = [
            "name" => "Adewalesss",
            "this" => "should zzzzssworkddd"
        ];
        /*
        * because this is not a dynamic value like session or a database content, 
        * it will only emit changes once
        */
    }

    public function profile(Response $response,?String $message,Sessions $sessions){

        return $response->json($this->profile);
    }
}