<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/22/2018
 * Time: 3:29 AM
 */

namespace Path\Controller;
load_class("Controller","controllers");
use Data\Database;
use Path\Controller;
use Path\Http\Request;
use Path\Http\Response;


class User implements Controller
{
    private $db_connection;

    public function __construct()
    {
//        $this->db_connection = $database;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function Delete(Request $request, Response $response){
            return $response->json(['user_id' => $request->params->user_id,"action" => "DELETE FROM CONTROLLER"]);

    }
    public function Find(Request $request,Response $response){
        return $response->json(['result' => (array) $request->params],200);
    }
    public function Auth(Request $request,Response $response){
        return true;
    }

}