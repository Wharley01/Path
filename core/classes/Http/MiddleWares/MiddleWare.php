<?php

namespace Path\Http;


interface MiddleWare
{
    public function __construct();

    /**
     * @param $request
     * @param $params
     * @return mixed
     */
    public function Control($request, $params);
}