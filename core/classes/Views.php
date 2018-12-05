<?php

namespace Path;


use Path\Http\Response;
load_class("Http/Response");

class Views
{
    /**
     * Views constructor.
     * @param mixed $raw_content
     */
    public function __construct(Mixed $raw_content)
    {

    }
    static public function Render($file_path,$status_code = 200):Response{
//        echo __DIR__;
        $path = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.$file_path;

        if(!file_exists($path))
            throw new PathException(" \"{$file_path}\" does not exist");

        return (new Response())->html(file_get_contents($path));
    }
}