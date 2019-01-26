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
    public function control(Request $request, Response $response):bool
    {
        return config("PROJECT->status") != "production";
    }


}