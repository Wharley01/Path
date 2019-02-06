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
    public function validate(Request $request, Response $response):bool;
    public function fallBack(Request $request, Response $response);

}