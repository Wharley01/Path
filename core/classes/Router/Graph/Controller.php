<?php
namespace Path\Core\Router\Graph;


use Path\Core\Http\Request;
use Path\Core\Http\Response;

abstract class Controller
{
    public $model;
    public function getOne(Request $request,Response $response){
//     return a response here

        return $response->success('Data fetched successfully',(clone $this->model)->getFirst());
    }
    public function getAll(Request $request,Response $response):Response{

        return $response->data(clone $this->model);
    }

    public function set(Request $request,Response $response):Response{

        return $response->success('successfully updated');
    }

    public function update(Request $request,Response $response):Response{

        return $response->success('successfully updated');
    }

    public function schema(){
//        specify service that can call it
        return [
            "getOne" => [
                "middleware" => [],
                "required_args" => []
            ]
        ];
    }
}
