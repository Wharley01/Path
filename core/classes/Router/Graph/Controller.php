<?php
namespace Path\Core\Router\Graph;


use Path\Core\Database\Model;
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
        if($this->model instanceof Model){
            $this->model->insert($request->getPost());
        }
        return $response->success('successfully updated',$request->getPost());
    }

    public function update(Request $request,Response $response):Response{
        if($this->model instanceof Model){
            $this->model->update($request->getPost());
        }
        return $response->success('successfully updated',$request->getPatch());
    }

    public function delete(Request $request,Response $response):Response{
        if($this->model instanceof Model){
            $this->model->delete();
        }
        return $response->success('successfully deleted');
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
