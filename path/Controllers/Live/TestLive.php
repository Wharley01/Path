<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/17/2019
 * @Time 1:23 AM
 * @Project Path
 */

namespace Path\Controller\Live;


use Path\LiveController;
use Path\Sessions;

class TestLive implements LiveController
{
    public $watch_list = [
        "isLogin" => false,
        "profile" => 0
    ];

    public $uri_template = "TestLive/isLogin/profile";
    public function __construct()
    {
        $this->watch_list['isLogin'] = !is_null(Sessions::get("user_login_id"));
        $this->watch_list['profile'] = Sessions::get("total_user");
    }
    public function profile(){

    }
}