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
    static public function Render($file){
//        echo __DIR__;
        return (new Response())->html(file_get_contents($_SERVER['DOCUMENT_ROOT']."views".DIRECTORY_SEPARATOR."{$file}.html"));
    }
}