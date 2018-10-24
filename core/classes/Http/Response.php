<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/23/2018
 * Time: 1:22 PM
 */

namespace Path\Http;


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
    public function json(array $arr,$status = 200){
        $this->content = json_encode($arr);
        $this->headers = array_merge($this->headers,["Content-Type" => "application/json; charset=UTF-8"]);
        return $this;
    }
    public function redirect($url){
        header("location: {$url}");
    }
    public function addHeader(array $header){
        $this->headers = array_merge($this->headers,$header);
        return $this;
    }



}