<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/5/2018
 * @Time 3:05 AM
 * @Project Path
 */

namespace Path\Http\MiddleWare;


use Path\Http\MiddleWare;
use Path\Http\Request;
use Path\Http\Response;
use Path\Storage\Sessions;

class isProd implements MiddleWare
{

    public function __construct()
    {
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @internal param $params
     */
    public function validate(Request $request, Response $response):bool
    {
        return config("PROJECT->status") == "production";
    }

    public function fallBack(Request $request, Response $response)
    {
            $sessions = new Sessions();
            $sessions->store("testing","helloxxxx chai, this xxxis goodccc lalal");

            return $response->json(['mode' => 'Development Mode',"session_id" => session_id()]);
    }
}