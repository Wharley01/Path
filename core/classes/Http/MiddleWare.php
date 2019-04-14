<?php

namespace Path\Core\Http;


interface MiddleWare
{

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @internal param $params
     */
    public function validate(Request $request, Response $response): bool;
    public function fallBack(Request $request, Response $response);
}
