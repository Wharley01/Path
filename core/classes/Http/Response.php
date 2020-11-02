<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 10/23/2018
 * Time: 1:22 PM
 */

namespace Path\Core\Http;

use Path\Core\Database\Model;
use Path\Core\Error\Exceptions;
use Path\Core\Error\Logger;
use Path\Core\Storage\Caches;
use Spatie\Ssr\Engines\Node;
use Spatie\Ssr\Engines\V8;
use Spatie\Ssr\Renderer;
use V8Js;


class Response
{
    public $content;
    public $status;
    public $headers = [
        'X-Powered-By' => 'PathPHP/1.5.4',
    ];
    public $state = [];
    private $head = [];
    private $bottom = [];
    private $lang = "ng";
    private $title = "A sample server-side rendered Path powered page";
    private $should_cache = false;
    public $ssr_route_path = null;
    public $metas = [];
    public $build_path = "/dist/";
    public $is_binary = false;
    public $is_sse = false;
    private $response_state;
    static $ASSETS_MANIFEST = null;

    public function __construct($build_path = '/dist/')
    {
        $this->build_path = $build_path ?? '/dist/';
        try{
            $this->generateManifest();
        }catch (\Throwable $exception){}
        return $this;
    }
    public function disableCORS($server = null){
        $headers = [
            "Access-Control-Allow-Credentials" => "true",
            "Access-Control-Allow-Methods" => "GET, POST, OPTIONS, PATCH",
            "Access-Control-Allow-Headers" => "Origin, Content-Type, Accept, Authorization"
        ];
        if($server){
            $servers = explode(",",$server);
            if ($origin = @parse_url($_SERVER["HTTP_REFERER"]) and @$origin['path']){
                $_base = $origin;
                $origin = $origin["scheme"] . "://" . $origin["host"];

                if(isset($_base["port"]))
                    $origin .= ":".$_base["port"];
            }
//            echo $origin;
            if(in_array($origin,$servers)){

                $headers['Access-Control-Allow-Origin'] = $origin;
            }
        }else{
            $headers['Access-Control-Allow-Origin'] = "*";
        }
        $this->headers = array_merge($this->headers,$headers);
        return $this;
    }
    private function generateManifest(){
        $manifest_filepath = ROOT_PATH.$this->build_path.'asset-manifest.json';
        if(file_exists(ROOT_PATH.$this->build_path)){
            if(!static::$ASSETS_MANIFEST){
                if(file_exists($manifest_filepath)){
                    if(!static::$ASSETS_MANIFEST = json_decode(file_get_contents($manifest_filepath))){
                        throw new Exceptions\Router('Invalid Manifest.json file in '.$this->build_path);
                    }
                }else{
                    throw new Exceptions\Router('manifest file does not exist, run: yarn build to generate one ');
                }

            }

        }
    }
    public function json($arr, $status = 200)
    {
//        $arr = $this->convertToUtf8($arr);
        $this->content = json_encode((array)$arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        $this->status = $status;
        $this->headers = array_merge(["Content-Type" => "application/json; charset=UTF-8"],$this->headers);
        return $this;
    }

    public function info($msg,$status = 200,$keys = []){
        $res = [
            "has_error" => true,
            "msg" => $msg,
            "data" => []
        ];

        $res = array_merge($res,$keys);
        return $this->json($res,$status);
    }
    public function error($msg,$fields = [],$status = 401, $keys = []){
        $res = [
            "has_error" => true,
            "msg" => $msg,
            "data" => $fields
        ];

        $res = array_merge($res,$keys);
        return $this->json($res,$status);
    }
    public function success($msg,$fields = [],$status = 200,$keys = []){
        $res = [
            "has_error" => false,
            "msg" => $msg,
            "data" => $fields
        ];

        $res = array_merge($res,$keys);
        return $this->json($res,$status);
    }
    public function data(Model $model,$page = null,string $msg = '',int $status = 200){
        $data = (clone $model)->getPage($page ?? $model->current_page);
        $has_data = count($data) > 0;
        return $this->json([
            "has_error" => false,
            "msg" => $msg,
            "data" => $data,
            "current_page" => $model->current_page,
            "total_pages" => $model->getTotalPages()
        ],!$has_data ? 201:$status);
    }

    public function raw($content, $status = 200)
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = array_merge(["Content-Type" => "application/octet-stream"],$this->headers);
        return $this;
    }
    public function text(String $text, $status = 200)
    {
        $this->content = $text;
        $this->status = $status;
        $this->headers = array_merge(["Content-Type" => "text/plain; charset=UTF-8"],$this->headers);
        return $this;
    }
    public function htmlString(String $html, $status = 200)
    {
        $this->content = $html;
        $this->status = $status;
        $this->headers = array_merge(["Content-Type" => "text/html; charset=UTF-8"],$this->headers);
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
        $this->headers = array_merge(["Content-Type" => "text/html; charset=UTF-8"],$this->headers);


        return $this;
    }
    public function stream($data, $status = 200)
    {
        $this->content = $data;
        $this->status = $status;
        $this->headers = array_merge(["Content-Type" => "application/octet-stream; charset=UTF-8"],$this->headers);
        return $this;
    }

    public function SSEStream($raw_data, $delay = 1000, $id = null, $status = 200)
    {
        if (!$id)
            $id = time();

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

    public function redirect($url, int $code = 302, bool $replace = true)
    {
        header("location: {$url}", $replace, $code);
        exit();
    }
    public function addHeader(array $header)
    {
        $this->headers = array_merge($this->headers,$header);
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


    public function setState($key, $value){
        $this->state[$key] = $value;
    }

    public function setHead(
        ...$heads
    ){
        $this->head = array_merge($this->head,$heads);
        return $this;
    }
    static function HTMLTag(string $tag,array $attrs){

        return [
            $tag => $attrs
        ];
    }

    private function arr2Tag(array $array,$should_cache = false){
        $html_elements = [];
        foreach ($array as $element){
            foreach ($element as $tag => $attrs){

                if(is_numeric($tag))
                    throw new Exceptions\Path("Head array should be associative array where the key is the tag and value is the attributes");

                $html = "<$tag";
                foreach ($attrs as $attr => $value){
                    if(is_numeric($attr))
                        throw new Exceptions\Path("Attribute array should be associative array where the key is the attribute and value is the attribute's value");
//                    check if attr is src or href
                    if((trim($attr) == 'src' || trim($attr) == 'href') && !$should_cache){
                        $rand = rand(33,63434345);
                        $html .= " {$attr}=\"{$value}?cache={$rand}\"";
                    }else{
                        $html .= " {$attr}=\"{$value}\"";
                    }
                }
                if($tag == 'script')
                    $html .="></$tag>";
                else
                    $html .=">";
                $html_elements[] = $html;
            }

        }

        return join("\n",$html_elements);
    }

    public function getHead($should_cache = false){

        if(!empty($this->head)){
            return $this->arr2Tag($this->head,$should_cache);
        }else{
            return "";
        }
    }

    public function setBottom(
        ...$bottom
    ){
        $this->bottom = array_merge($this->bottom,$bottom);
    }

    public function getBottom($should_cache = false){
        if(!empty($this->bottom)){
            return $this->arr2Tag($this->bottom,$should_cache);
        }else{
            return "";
        }
    }

    public function setRoutePath(
        $route_path = null
    ){
        $router = new Router();
        if(!$route_path){
            $this->ssr_route_path = $router->browser_path;
        }else{
            $this->ssr_route_path = $route_path;
        }
        return $this;
    }

    public function shouldCache(bool $status = false){
        $this->should_cache = $status;

        return $this;
    }

    public function cacheIsValid($hashed_entry){
        $state_salt = md5(json_encode($this->state));
        $cache_salt = md5($hashed_entry.$state_salt);
        $cache_path = $hashed_entry.'_state';

        if ($cache_salt != Caches::get($cache_path)) {
            Caches::set($cache_path, $cache_salt);
            return false;
        }else{
            return true;
        }

    }

    public function SSR(
        $entry = null,
        $status = 200
    ){
//        entry points
        $this->generateManifest();
        $entry = $entry ?? @($this->build_path.static::$ASSETS_MANIFEST->entrypoints->server->js[0]) ?? null;

        $router = new Router();
        $route = $this->ssr_route_path ?? $router->real_path;
        $hashed_entry = md5($entry.$route);

//        get hashed state

        if(config('PROJECT->is_production') && $this->cacheIsValid($hashed_entry)){
            $html =  Caches::get($hashed_entry);
        }else{
            $html = $this->compileJs($entry,$route);
            Caches::cache($hashed_entry,$html);
        }

        $state = json_encode($this->state);

        return $this->htmlString($this->generateHTML(
            $html,
            $state,
            $this->build_path.'asset-manifest.json'
        ),$status);
    }
    /**
     * @param string $lang
     * @return Response
     */
    public function setLang(string $lang): Response
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @param string $title
     * @return Response
     */
    public function setTitle(string $title): Response
    {
        $this->title = $title;
        return $this;
    }

    private function compileJs(?string $entry, $route)
    {
        if(config("SSR->is_node")){
            $engine = new Node(config("SSR->node_path") ?? 'node', ROOT_PATH.'path/.Storage');
            $renderer = new class($engine) extends Renderer{
                protected function dispatchScript(): string
                {
                    return <<<JS
var dispatch = function (result) {
    return {$this->engine->getDispatchHandler()}(result)
}
JS;
                }
            };
        } else{
            $engine = new V8(new V8Js());
            $renderer = new Renderer($engine);
        }

        $html = $renderer
            ->debug(true)
            ->enabled(true)
            ->env('VUE_ENV','server')
            ->env('NODE_ENV','development')
            ->context('state',$this->state ?? [])
            ->context('route',$route)
            ->entry(ROOT_PATH.$entry)//
            ->render();
        return $html;
    }

    private function fetchMainClientJs(){
        $assets = (object) static::$ASSETS_MANIFEST;

        $main_client = $this->build_path.$assets->entrypoints->client->js[0];
//
        $this->setHead(
            Response::HTMLTag('link',[
                'href' => $main_client,
                'rel' => 'preload',
                'as' => 'script'
            ])
        );

        $this->setBottom(
            Response::HTMLTag('script',[
                "src" => $main_client
            ])
        );
    }

    private function fetchMainClientCSS(){
        $assets = (object) static::$ASSETS_MANIFEST;

        $main_client = $this->build_path.$assets->entrypoints->server->css[0];

        $this->setHead(
            Response::HTMLTag('link',[
                'href' => $main_client,
                'rel' => 'preload',
                'as' => 'style'
            ])
        );

        $this->setHead(
            Response::HTMLTag('link',[
                "href" => $main_client,
                "rel" => "stylesheet"
            ])
        );
    }

    private function preFetchAssets(){
        $ingores = [
            'server.js',
            'client.js',
            'server.css',
            'client.css'
        ];
        $assets = (array) static::$ASSETS_MANIFEST;
        unset($assets['entrypoints']);//delete entry points

        foreach ($assets as $asset => $path){
            $split = explode('.',$asset);
            $type = end($split);

            if(in_array($asset,$ingores)){
//                echo 'should ignore: '.$asset;
                continue;
            }

            if($type == 'js'){
                $this->setHead(
                    Response::HTMLTag('link',[
                        'href' => $this->build_path.$path,
                        'rel' => 'prefetch'
                    ])
                );
            }else{
                $this->setHead(
                    Response::HTMLTag('link',[
                        'href' => $this->build_path.$path,
                        'rel' => 'preload'
                    ])
                );
            }

        }

    }

    private function generateHTML(string $html, string $state, string $string)
    {
        $this->preFetchAssets();
        $this->fetchMainClientCSS();
        $this->fetchMainClientJs();


        $head = $this->getHead($this->should_cache);
        $bottom = $this->getBottom($this->should_cache);
        $state = json_encode($this->state);

        return str_replace([
            '<data:head></data:head>',
            '<data:scripts></data:scripts>'
        ], [
            $head,
            "<script>window.__INITIAL_STATE__ = {$state}</script>{$bottom}"
        ],

            $html);
    }


}
