<?php
/**
 * Created by PhpStorm.
 * User: HP ENVY
 * Date: 7/14/2018
 * Time: 4:42 AM
 */

namespace Path\Core\Http;


class Request
{
    public $METHOD;
    public $server;
    public $params;
    public $filters;
    public $args;
    public $inputs;
    public $fetching;
    public $headers = [];
    private $sending_post_feilds = [];
    private $sending_post_json_fields = null;
    private $sending_query_fields = [];
    private $IP;
    private $post;
    private $files;
    private $as_local = false;
    //    CONST
    /**
     * @var array
     */
    private $sending_patch_fields = [];
    private $timeout = 30;
    /**
     * @var bool
     */
    private $redirect = true;

    public function __construct()
    {
        $this->server = (object)$_SERVER;
        $this->METHOD = strtoupper($this->getQuery('_method') ?? $_SERVER["REQUEST_METHOD"] ?? "GET");
        $input = file_get_contents("php://input");
        $input_arr = [];

        if(is_string($input) && strlen($input) > 0){
            if($_input = json_decode($input, true)){
                $input_arr = $_input;
            }else{
                parse_str($input,$input_arr);
            }
        }else{
            $input_arr = [];
        }

        if ($this->METHOD === 'POST' || $this->METHOD === 'PATCH' || $this->METHOD === 'PUT') {
            $this->post = array_merge($input_arr,$_POST);
            $this->inputs = $this->post;
        }




        if (!@$_SERVER['REDIRECT_URL'])
            $_SERVER['REDIRECT_URL'] = "/";


    }
    public function fetch($key)
    {
        return @$_REQUEST[$key] ?? null;
    }

    /**
     * @param null $key
     * @return mixed
     */
    public function getPost($key = null){
        $posts = $this->inputs;
        $posts = array_merge($posts ?? [],$this->sending_post_feilds);
        if(!is_null($key))
            return $posts[$key] ?? null;

        return $posts;
    }
    /**
     * @param null $key
     * @return mixed
     */
    public function getHeader($key = null){
        $heads = $this->headers;
        if(!is_null($key))
            return $heads[strtoupper($key)] ?? null;
        return $heads;
    }


    /**
     * @param null $key
     * @return mixed
     */
    public function getPatch($key = null){

        $patches = array_merge($this->inputs ?? [],$this->sending_patch_fields);

        if(!is_null($key))
            return $patches[$key] ?? null;

        return $patches ?? [];
    }


    public function getQuery($key = null){
        $queries = array_merge($_GET,$this->sending_query_fields);
        if(!is_null($key))
            return $queries[$key] ?? null;

        return $queries;
    }


    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->params = (object) $params;
        return $this;
    }

    public function getParam($param = null){
        return $this->params->{$param} ?? null;
    }

    public  function file($name)
    {
        $file = @$_FILES[$name];
        if(!$file){
            $this->fetching = [];
            return null;
        }
        $files = is_array($file['name']) ? $this->restructure($file) : [$file];
        $this->fetching = $files;

        return $this;
    }

    private function restructure($file)
    {
        $file_ary = array();
        $file_count = count($file['name']);
        $file_key = array_keys($file);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_key as $val) {
                $file_ary[$i][$val] = $file[$val][$i];
            }
        }
        return $file_ary;
    }

    public  function getFile($name,$as_array = false)
    {
        $file = @$_FILES[$name];

        if(!$file){
            $this->fetching = [];
            return $as_array ? null:$this;
        }

        $files = is_array($file['name']) ? $this->restructure($file) : [$file];
        $this->fetching = $files;

        return $as_array ? (!$file ? null: $files):$this;
    }

    public function setRequestIP($IP)
    {
        $this->headers['REMOTE_ADDR'] = $IP;
        $this->headers['HTTP_X_FORWARDED_FOR'] = $IP;
        $this->IP = $IP;
        return $this;
    }

    public function asLocal(){
        $this->as_local = true;
        return $this;
    }

    public function setPost(array $fields)
    {
        $this->sending_post_feilds = array_merge($this->sending_post_feilds,$fields);
//        $this->inputs = $fields;
        return $this;
    }

    public function overridePost(?array $fields){
        if($fields)
            $this->inputs = $fields;
        return $this;
    }

    public function setFile(string $key, $file_path, $file_details = [])
    {

        $file_name = $file_details['name'] ?? basename($file_path);
        $file_type = $file_details['type'] ?? null;
        $file_size = $file_details['size'] ?? null;

        if(!$file_path)
            throw new \Path\Core\Error\Exceptions\Router('File path not specified');
        if(!is_file($file_path))
            throw new \Path\Core\Error\Exceptions\Router('FIle path not valid');
        $_file = new \CURLFile($file_path,$file_type,$file_name);

        $this->files[$key] = $_file;
        $i = 0;
        foreach ($this->files as $_key => $file){
            $this->sending_post_feilds[$_key."[$i]"] = $file;
            $i ++;
        }

//        $this->inputs = $fields;
        return $this;
    }

    public function dump(){
        var_dump($this->sending_post_feilds);
    }
    public function setPatch(array $fields)
    {
        $this->sending_patch_fields = $fields;
//        $this->inputs = $fields;
        return $this;
    }

    public function setArgs(array $fields)
    {
        $this->args = array_merge($this->args,$fields);
//        $this->inputs = $fields;
        return $this;
    }

    public function setPostJson(array $fields)
    {
        $this->sending_post_json_fields = json_encode($fields);
        return $this;
    }

    public function setQuery(array $fields)
    {
        $this->sending_query_fields = array_merge($this->sending_query_fields,$fields);
        $_GET = $fields;
        return $this;
    }

    private function buildRawHeader(array $headers = null)
    {
//        var_dump($this->headers);
        $headers = $headers ?? $this->headers;
        $header = [];
        foreach ($headers as $key => $value) {
            $header[] = "{$key}: {$value}";
        }

        return $header;
    }

    public function setHeader(array $headers = [])
    {
        $this->headers = $headers;
    }

    /**
     * @param int $timeout
     * @return Request
     */
    public function setTimeout(int $timeout): Request
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function followRedirection(bool $redirect = true): Request
    {
        $this->redirect = $redirect;
        return $this;
    }

    private function httpRequest($url, $method): ?Response
    {
        $ch = curl_init();
        if ($this->sending_query_fields) {
            $query = http_build_query($this->sending_query_fields);
            $url .= "?" . $query;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->redirect);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if($this->as_local){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if ($this->IP) {
            curl_setopt($ch, CURLOPT_PROXY, $this->IP);
        }
        if ($this->sending_post_feilds) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->sending_post_feilds);
        }
        if($this->sending_post_json_fields){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->sending_post_json_fields);
        }
//        curl_setopt($ch, CURLOPT_HEADER, 1);
        if($method == 'HEAD'){
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }
//        if($method == 'POST'){
//            curl_setopt($ch, CURLOPT_POST,1);
//        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $head = $this->buildRawHeader();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);

        $headers = [];
        // Then, after your curl_exec call:
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) >= 2)
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
            return $len;
        }); //get headers as array

        $raw_response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $response = new Response();
        $response->addHeader($headers);
        $response->status = $status;
        $response->content = $raw_response;

        curl_close($ch);

        return $response;
    }

    public function get($url): ?Response
    {
        return $this->httpRequest($url, "GET");
    }

    public function post($url): ?Response
    {
        return $this->httpRequest($url, "POST");
    }
    public function head($url): ?Response
    {
        return $this->httpRequest($url, "HEAD");
    }
    public function delete($url): ?Response
    {
        return $this->httpRequest($url, "DELETE");
    }

    public function getAny(string $key)
    {
        return $this->getQuery($key) ?? $this->getPost($key) ?? $this->getPatch($key);
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getArg(?string $key = null)
    {
        if($key){
            return $this->args->$key ?? null;
        }
        return (array) $this->args;
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getFilters(?string $key = null)
    {
        if($key){
            return $this->filters[$key] ?? null;
        }
        return (array) $this->filters;
    }

    /**
     * @param array $fields
     * @return Request
     */
    public function setFilters(array $fields)
    {
        $this->filters = $fields;
        return $this;
    }
}
