<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/23/2018
 * Time: 1:22 PM
 */

namespace Path\Http;
load_class(["Http/MiddleWares/MiddleWare","Http/MiddleWares/Auth"]);

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
        $this->status = $status;
        $this->headers = array_merge($this->headers,["Content-Type" => "application/json; charset=UTF-8"]);
        return $this;
    }
    public function text(String $text,$status = 200){
        $this->content = $text;
        $this->status = $status;
        $this->headers = array_merge($this->headers,["Content-Type" => "text/plain; charset=UTF-8"]);
        return $this;
    }
    public function html(String $html,$status = 200){
        $this->content = $html;
        $this->status = $status;
        $this->headers = array_merge($this->headers,["Content-Type" => "text/html; charset=UTF-8"]);
        return $this;
    }
    public function stream($data,$status = 200){
        $this->content = $data;
        $this->status = $status;
        $this->headers = array_merge($this->headers,["Content-Type" => "application/octet-stream; charset=UTF-8"]);
        return $this;
    }
    public function redirect($url){
        header("location: {$url}");
    }
    public function addHeader(array $header){
        $this->headers = array_merge($this->headers,$header);
        return $this;
    }

    /**
     * @param $method
     * @param null $fallback
     * @return object
     */
    public static function MiddleWare(
        $method,
$fallback = null
    ){
        $fallback = is_callable($fallback) ? $fallback:$fallback;
        return (object)["method" => $method,"fallback" => $fallback];
    }



}