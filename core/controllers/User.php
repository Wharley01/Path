<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/22/2018
 * Time: 3:29 AM
 */

namespace Path\Controller;
load_class("Controller");
load_class(["Database/Model","Database/Models/User","FileSys"]);
use Data\Database;
use Path\Controller;
use Path\FileSys;
use Path\Http\Request;
use Path\Http\Response;

class User implements Controller
{
    protected $userModel;
    protected $fileSys;
    public function __construct()
    {
        $this->userModel = (new Database\User());
        $this->fileSys = new FileSys();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function Delete(Request $request, Response $response){
            return $response->json(['user_id' => @$request->params->user_id,"action" => /** @lang text */"DELETE FROM CONTROLLER"]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function UploadPicture(Request $request, Response $response){
//      get set from client
        $file_process = (new FileSys)
                        ->file($request->file("image"))//multiple file upload out of the box supported
                        ->setRules([
                        'retain_name' => false,//unique file name will be generated if set false
                        ])
                        ->moveTo("img/");//save in folder img/

        return $file_process->hasError() ? $response->json(["error" => [
            "msg" => "There was an error uploading your picture",
            "errors" => $file_process->getErrors()//get all those errors
        ]], 500) : $response->json(["files" => $file_process->getFiles()], 200);
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