<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/17/2019
 * @Time 1:23 AM
 * @Project Path
 */

namespace Path\Controller\Live;


use Path\Cache;
use Path\Http\Response;
use Path\LiveController;
use Path\Sessions;

class TestLive implements LiveController
{
    public $watch_list = [
        "isLogin" => false,
        "profile" => 0
    ];

    public function __construct(Response $response,$params)
    {
//        var_dump($params['name']);
        $this->watch_list['isLogin'] = true;
        $this->watch_list['profile'] = Cache::get("profile_name");
    }
    public function profile(){
        echo "Coming from profile";
        flush();
        ob_flush();
        return " this is a profile data from function profile_data";
    }
}