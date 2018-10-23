<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/23/2018
 * Time: 1:22 PM
 */

namespace Path;


class Response
{
    public $content;
    public $status;
    public $headers = [];
    public function __construct($content = "",$status = 200)
    {
        $this->content = $content;
        $this->status = $status;
        return $this;
    }
    public function addHeader(array $header){
        $this->headers = array_merge($this->headers,$header);
        return $this;
    }



}