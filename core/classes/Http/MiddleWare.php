<?php

namespace Path\Http;


interface MiddleWare
{
    public function __construct();

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @internal param $params
     */
    public function control(Request $request, Response $response):bool;

}