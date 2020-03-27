<?php

/*
* This is automatically generated
* Edit to fit your need
* Powered By: Path
*/

namespace Path\App\Controllers\Graph;


use Path\App\Http\MiddleWares\StopBlog;
use Path\Core\Router\Graph\Controller;
use Path\Core\Http\Request;
use Path\Core\Http\Response;
use Path\Core\Storage\Sessions;

use Path\App\Database\Model;



class Blog extends Controller
{
    public $model;
    public function __construct()
    {
        $this->model = new Model\Blog();

    }

    public function schema(){
//        specify service that can call it
        return [
            "getAll" => [
                "middleware" => StopBlog::class,
                "required_args" => ['post_id'],
                "required_params" => []
            ]
        ];
    }

}
