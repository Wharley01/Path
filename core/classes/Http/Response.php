<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/23/2018
 * Time: 1:22 PM
 */

namespace Path\Core\Http;

use Path\Core\Error\Exceptions;


class Response
{
    public $content;
    public $status;
    public $headers = [];
    public $build_path = "";
    public $is_binary = false;
    public $is_sse = false;
    private $response_state;
    public function __construct($build_path = '/')
    {
        $this->build_path = $build_path;
        return $this;
    }
    public function json($arr, $status = 200)
    {
        $arr = $this->convertToUtf8($arr);
        $this->content = json_encode((array)$arr, JSON_PRETTY_PRINT);
        $this->status = $status;
        $this->headers = ["Content-Type" => "application/json; charset=UTF-8"];
        return $this;
    }
    public function text(String $text, $status = 200)
    {
        $this->content = $text;
        $this->status = $status;
        $this->headers = ["Content-Type" => "text/plain; charset=UTF-8"];
        return $this;
    }
    public function htmlString(String $html, $status = 200)
    {
        $this->content = $html;
        $this->status = $status;
        $this->headers = ["Content-Type" => "text/html; charset=UTF-8"];
        return $this;
    }
    public function bindState(array $state)
    {
        $this->response_state = $state;
        return $this;
    }
    public function html($file_path, $status = 200)
    {
        $state = $this->response_state;
        $public_path = treat_path($this->build_path);

        $load_file = function ($file_path) {
            $file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $this->build_path . "/" . $file_path;
            if (!file_exists($file))
                throw new Exceptions\Path(" \"{$file_path}\" does not exist");
            $file_content = file_get_contents($file);
            return $file_content;
        };

        $link_resources = function ($raw_data) use ($public_path) {
            return preg_replace_callback('/(href|src)=\"?([^">\s]+)\"?[^\s>]*/m', function ($matches) use ($public_path) {
                //            var_dump($matches);
                $resources_path = explode("/", $matches[2]);
                array_shift($resources_path);
                $resources_path = join("/", $resources_path);

                return "$matches[1]='{$public_path}{$resources_path}'";
            }, $raw_data);
        };

        $replace_variables = function ($raw_data) use ($state) {
            return preg_replace_callback('/{{(\w+)}}/m', function ($matches) use ($state) {
                $split = explode("->", $matches[1]);
                $value = $state[$split[0]];
                for ($i = 1; $i < count($split); $i++) {
                    $value = @$value[$split[$i]];
                }
                return $value;
            }, $raw_data);
        };

        $load_includes = function ($raw_data) use ($state, $replace_variables, $load_file) {
            return preg_replace_callback('/{{(@include\s*=\s*"(.*)"\s*)}}/m', function ($matches) use ($state, $replace_variables, $load_file) {
                $file_path = $matches[2];
                $load = $load_file($file_path);
                return $load;
            }, $raw_data);
        };


        $file_content = $load_file($file_path);
        //      get the content

        $match_resources = $link_resources($file_content);

        //{{(@include="(.*)")}}
        $match_resources = $load_includes($match_resources);

        //replace variables
        $match_resources = $replace_variables($match_resources);

        $this->content = $match_resources;
        $this->status = $status;
        $this->headers = ["Content-Type" => "text/html; charset=UTF-8"];

        return $this;
    }
    public function stream($data, $status = 200)
    {
        $this->content = $data;
        $this->status = $status;
        $this->headers = ["Content-Type" => "application/octet-stream; charset=UTF-8"];
        return $this;
    }

    public function SSEStream($raw_data, $delay = 300, $id = null, $status = 200)
    {
        if (!$id)
            $id = time();

        //        [
        //          "event" => "response",
        //        ];
        $data = "";
        $data .= "id: {$id}" . PHP_EOL;
        if (is_array($raw_data)) {
            foreach ($raw_data as $event => $response) {
                $data .= "event: $event" . PHP_EOL;
                if (is_array($response))
                    $response = json_encode($response);

                $response = preg_split('/$\R?^/m', $response);
                foreach ($response as $line) {
                    if (strlen(trim($line)))
                        $data .= "data: {$line}" . PHP_EOL;
                }
                $data .= PHP_EOL;
            }
        } else {
            if (strlen($raw_data) > 0) {
                $response = preg_split('/$\R?^/m', $raw_data);
                foreach ($response as $line) {
                    if (strlen(trim($line)))
                        $data .= "data: {$line}" . PHP_EOL;
                }
                $data .= PHP_EOL;
            }
        }
        $this->content = 'delay: ' . $delay . PHP_EOL . $data;
        $this->status = $status;
        $this->headers = [
            "Content-Type" =>  "text/event-stream; charset=UTF-8",
            "Cache-Control" => "no-cache"
        ];
        return $this;
    }

    public function redirect($url)
    {
        header("location: {$url}");
    }
    public function addHeader(array $header)
    {
        $this->headers = array_merge($this->headers, $header);
        return $this;
    }
    public function file($file_path)
    { }

    public function image($path, $type = "image/jpeg")
    {
        $path = ROOT_PATH . "/" . $path;
        if (!is_file($path))
            throw new \Exception("Invalid File {$path}");
        $this->content = $path;
        $this->headers = array_merge(
            $this->headers,
            [
                "Content-Length" => filesize($path),
                "Content-type" => $type
            ]
        );
        $this->is_binary = true;
        return $this;
    }

    private function convertToUtf8($d)
    {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = $this->convertToUtf8($v);
            }
        } else if (is_string($d)) {
            return utf8_encode($d);
        }
        return $d;
    }
}
