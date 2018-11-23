<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/22/2018
 * Time: 3:29 AM
 */

namespace Path\Controller;
load_class("Controller","controllers");
load_class(["Database/Model","Database/Models/User"]);
use Data\Database;
use Path\Controller;
use Path\Http\Request;
use Path\Http\Response;
use Path\Model;


class User implements Controller
{
    private $db_connection;
    protected $userModel;
    public function __construct()
    {
        $this->userModel = (new Model\User());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function Delete(Request $request, Response $response){
            return $response->json(['user_id' => @$request->params->user_id,"action" => /** @lang text */"DELETE FROM CONTROLLER"]);
    }
    public function Find(Request $request,Response $response){
        return $response->json(["total" => $this->userModel->where("Name")->like("%{$request->params->user_name}%")->all()],200);
    }
    public function Profile(Request $request,Response $response){
        return $response->json($this->userModel->identify($request->params->user_id)->first(),200);
    }
    public function Auth(Request $request,Response $response){
        $result = $this->userModel
                       ->identify(@$request->params->user_id);
        return $result->count() > 0;
    }

}