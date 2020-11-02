<?php


namespace Path\Core\Http;


use Path\Core\Database\Model;
use Path\Core\Error\Exceptions\Path;
use Path\Core\Error\Exceptions\ResponseError;
use Path\Core\Router\Graph\Controller;
use Path\Core\Router\Route;
use Path\App\Controllers\Graph;

class PathGraph extends Route\Controller
{
    private $controller_namespace = "Path\\App\\Controllers\\Graph\\";
    private $request_method = "GET";
    private $auto_link = null;

    private $http_verb_func_pair = [
        "POST" => "set",
        "PATCH" => "update",
        "DELETE" => "delete"
    ];
    private $allowed_key_pattern = "/^([^\s\W]+)$/";
    private $page = 1;

    public function onOptions(Request $request, Response $response)
    {
        return $response->success('PREFLIGHT');
    }

    public function onGet(Request $request, Response $response)
    {
        return $this->executeQuery($request,$response);
    }

    public function onPost(Request $request, Response $response)
    {
        return $this->executeQuery($request,$response);
    }

    public function onDelete(Request $request, Response $response)
    {
        return $this->executeQuery($request,$response);
    }

    public function onPatch(Request $request, Response $response)
    {
        return $this->executeQuery($request,$response);
    }

    private function validateAllMiddleWare($middle_wares, &$request, $response, $_path, &$model = null)
    {
        if (!is_array($middle_wares) || is_string($middle_wares))
            $middle_wares = [$middle_wares];


        foreach ($middle_wares as $middle_ware) {
            if ($middle_ware) {

                $argument = null;
                //            Load middleware class
                if(is_array($middle_ware)){
                    $ini_middleware = new $middle_ware[0]();
                    $argument = $middle_ware[1] ?? null;
                }elseif (is_string($middle_ware)){
                    $ini_middleware = new $middle_ware();

                }else{
                    throw new ResponseError('Invalid Middle passed');
                }

// TODO: added magic arg property
                $ini_middleware->arg = $argument;
                $ini_middleware->model = &$model;
                if ($ini_middleware instanceof MiddleWare) {

                    //            initialize middleware

                    //            Check middle ware return
                    $check_middle_ware = $ini_middleware->validate($request, $response);
                    if (!$check_middle_ware) { //if the middle ware control method returns false
                        //                call the fall_back response
                        $fallback_response = $ini_middleware->fallBack($request, $response);

                        if (!is_null($fallback_response)) { //if user has a fallback method

                            if ($fallback_response && is_array($fallback_response)) {
                                return $fallback_response;
                            } elseif($fallback_response instanceof Response) {
                                return $fallback_response;
                            }else{
                                throw new ResponseError("Invalid middleware Response at $_path");
                            }
                        }else{
                            throw new ResponseError("Invalid middleware Response at $_path");
                        }
                    }else{
                        continue;
                    }
                } else {
                    throw new ResponseError("Expected \"{$middle_ware->method}\" to implement \"Path\\Http\\MiddleWare\" interface in \"{$_path}\"");
                }
            }
        }
        return false;
    }

    public function executeQuery(Request $request, Response $response)
    {
        $query = $request->getQuery();
        $method = strtoupper($request->METHOD);
        $this->auto_link = $request->getQuery('auto_link');
        $this->request_method = $method;

        if(!$query){
            return $response->error("Invalid graph structure");
        }

        try {
//            $response_data = [];
            $data = $this->generateResponseData($query,null);
            return $data;
        } catch (\Throwable $e) {
            if(method_exists($e,'getResponse')){
                $res = $e->getResponse();
                if($res){
                    return $res;
                }
            }

            return $response->error($e->getMessage());
        }
    }

    private function serviceToTable($input){

        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return strtolower(implode('_', $ret));
    }

    private function generateResponseData($query,$parent = null,$parent_tbl_name = null, $args = null){
        $req = &$this->request;
        $req->METHOD =  $this->request_method;

//        var_dump($req->METHOD);

        $req->args = $args;

        $res = (clone $this->response);
        $req->overridePost($this->getPostParams($req));

        $data = [];
        foreach ($query as $service => $structure){
            $table_name = $this->serviceToTable($service);
//            echo $table_name;
            $service = $this->controller_namespace. $service;
            $service_name = $this->controller_namespace. $service;
            if(!class_exists($service)){
                throw new  ResponseError("Service \"".$service_name."\" does not exist" );
            }
            $service = new $service();
//            check for middleware
            if (key_exists('func',$structure)){
                $func = $structure['func'];
            }else if(array_key_exists($this->request_method,$this->http_verb_func_pair)){
                $func = $this->http_verb_func_pair[$this->request_method];
            }else{
                throw new ResponseError("No function specified");
            }
//            var_dump($func);

//            page
            $page = $structure['page'] ?? 1;

//            search query
            $search_query = $structure['query'] ?? null;

            if($search_query)
                $search_query = json_decode($search_query,true) ?? null;

            $filters = json_decode($structure['filters'] ?? null,true) ?? [];

            try {
                $params = $structure['params'] ?? [];
                if($params) $params = $this->cleanAndValidateKeys($params);

                $req->setParams($params);
            } catch (Path $e) {
                throw new ResponseError($e->getMessage());
            }

            if($service instanceof Controller){
//                Check for rules
                $model = $service->model;

// perform DB operations on the odel to be used by function
//                echo "He;";
                if($model instanceof Model){
                    $pry_key = $table_name.'.id';
                    $self_id = null;
                    if($this->auto_link && $parent){
                        $self_id_key = strtolower($table_name).'_id';
                        $self_id = $parent->$self_id_key ?? '______id____';//don't remove
//                        echo $self_id_key;
                    }

                    if($self_id){
                        $filters['id'] = $self_id;
                        $model->identify($self_id);
                    }elseif(array_key_exists($pry_key,$filters)){
                        $id = $filters['id'];

                        if(!is_numeric($id)){
                            throw new ResponseError("ID must be numeric" );
                        }else{
                            $model->identify($id);
                        }
                        unset($filters['id']);
                    }

                    $core_filters = $service->filters($req);

                    $req->setFilters($filters);

                    if (is_array($core_filters)){
                        $filters = array_merge($filters ?? [],$core_filters);
                    }
                    $filters = $this->cleanAndValidateKeys($filters, $table_name);

                    $model->where($filters);
//                    check if auto link is enabled
                    $model->setPage($page);
//                    search if search query exists
                    if($search_query){
                        $keyword = $search_query['keyword'] ?? null;
                        if(is_string($keyword) && strlen(trim($search_query['keyword'])) > 0){
                            if(isset($search_query['col'])){
                                $cols = explode(',',$search_query['col']);
//                                $model->whereColumns(...$cols)->matches($search_query['keyword']);
                                $model->where(function (Model &$model) use ($keyword,$cols){
                                    foreach ($cols as $index => $col){
                                        if($index == 0){
                                            $model->whereColumns($col)->matches($keyword);
                                        }else{
                                            $model->orWhereColumns($col)->matches($keyword);
                                        }
                                    }
                                });

                            }

                        }
                    }

                }
                if(method_exists($service,'rules')){
//
                    $rules = $service->rules();
                    if(!is_array($rules)){
                        throw new ResponseError("Error!: returned value of \"rules()\"  in ".$service_name." is expected to be an array, ".gettype($rules)." found" );
                    }

                    if(array_key_exists($func,$rules)){
                        $rules = $rules[$func];
//                        Check for middleware
                        //                        validate required params
                        if(array_key_exists('required_args',$rules) && $args !== null){
                            $this->validateRequiredArgs($rules['required_args'],$args,$func,$service_name);
                        }
//                        validate required params
                        if(array_key_exists('required_params',$rules)){
                            $this->validateRequiredParams($rules['required_params'],$req->params,$func,$service_name);
                        }
                        if(array_key_exists('middleware',$rules)){
                            $middleware_response = $this->validateAllMiddleWare(
                                $rules['middleware'] ?? [],
                                $req,
                                $res,
                                $service_name,
                                $model
                            );
                            if($middleware_response !== false){
                                if($parent === null){
                                    throw new ResponseError(null,0,null, $middleware_response);
                                }else{
                                    if($middleware_response instanceof Response){
                                        $r = json_decode($middleware_response->content);
                                        return property_exists($r,'data') ? $r->data : $r;
                                    }else{
                                        $r =  (object) $middleware_response;
                                        return property_exists($r,'data') ? $r->data : $r;
                                    }
                                }

                            }

                        }
                    }
                }
//
                if(!method_exists($service,$func)){
                    throw new ResponseError("Error!: trying to access service function \"".$func."\" that does not exist in ".$service_name );
                }

                if(!property_exists($service,'model')){
                    throw new ResponseError("Error!: $service_name does not have 'model' property" );
                }


                $columns = @$structure['columns'];

                if($this->request_method == "GET" && $model){
                    $this->generateSelectColumnsToModel($model,$columns,$table_name);
                }

                $res = $service->{$func}($req,$res);
                $message = "";
                $status = 200;
                $total_pages = 1;
                $current_page = 1;

                $last_ins_id = $model instanceof Model ? ($model->last_insert_id ?? null):null;

                if($res instanceof Response || is_array($res)){
                    $status = $res->status ?? 200;
                    if(is_array($res)){
                        $data = (object) [];
                        $data->data = $res;
                    }else{
                        $data = json_decode($res->content) ?? null;
                    }
                    if($data){
                        if(!property_exists($data,'data')){
                            throw new ResponseError("Unable to find data key in response returned in $service_name->$func");
                        }else{
                            $message = $data->msg ?? "";
                            $total_pages = $data->total_pages ?? 1;
                            $current_page = $data->current_page ?? 1;
                            $data = $data->data;
                        }
                    }else{
                        throw new ResponseError("Expected returned value of $service_name->$func to be either error/success/data of ".Response::class." or an array" );
                    }
//                    loop through column
                        $this->generateSelectColumnsToRes(
                            $data,
                            $columns,
                            $table_name,
                            $last_ins_id
                        );

                    if(!$parent) {
                        if ($this->response instanceof Response) {
                            return (clone $this->response)->success($message,$data,$status,[
                                "total_pages" => $total_pages,
                                "current_page" => (int) $page
                            ]);
                        }
                    }else{
                        return $data;
                    }
                }else{
                    throw new ResponseError("Expected returned value of $service_name->$func to be Instance of ".Response::class );
                }
            } else{
                throw new ResponseError("{$service_name} is not an instance of  ". Controller::class);
            }
        }
    }
    private function getPostParams(Request $req){
        $posts = $req->getPost();
        return $posts;
    }
    private function generateSelectColumnsToModel(Model &$model, $columns,$table_name){
        if(!$columns)
            return;

        //        select ID regardless

        $model->select(strtolower($table_name).'.id');

        foreach ($columns as $column => $det){
//            var_dump($det);
            if($det['type'] == "column"){
                $model->select(strtolower($table_name).'.'.$column);
            }
        }

    }
    public function getServiceResponse($column, $service, $service_tree, &$parent_data, $ref_id, $parent_tbl_name){
        $args = clone $parent_data;

        $ref_id = $ref_id ?? $parent_data->id ?? null;
        if($ref_id && $parent_tbl_name){
            $args->{$parent_tbl_name.'_id'} = $ref_id;
        }
        unset($args->$column);

        return $this->generateResponseData([
            $service => $service_tree
        ],$parent_data,$parent_tbl_name, $args);

    }
    private function cleanAndValidateKeys($data,$table_name = null){
        $res = [];
        if($data = is_string($data) ? json_decode($data,true):$data){
            foreach ($data as $key => $value){
//                var_dump(preg_match_all("/[^\w_]/",$key));
                if(!preg_match_all($this->allowed_key_pattern,$key)){
                    throw new ResponseError("Invalid key \"$key\", keys must match $this->allowed_key_pattern");
                }else{
                    if($table_name)
                        $res[$table_name.'.'.$key] = $value;
                    else
                        $res[$key] = $value;
                }
            }
            return $res;
        }else{
            return $res;
        }
    }

    private function validateRequiredArgs($required_args, $args, $func, $service)
    {
        foreach ($required_args as $index  => $arg){
            if(!property_exists($args,$arg)){
                throw new ResponseError("$func of  requires $arg Argument, make sure $arg is selected in your Parent service");
            }
        }
    }
    private function validateRequiredParams($required_params, $args, $func, $service)
    {
        foreach ($required_params as $index  => $arg){
            if(!property_exists($args,$arg)){
                throw new ResponseError("$func of  requires $arg parameter, make sure $arg is added to your parameter");
            }
        }
    }
    private function generateSelectColumnsToRes(
        &$data,
        $columns,
        $tbl_name,
        $primary_key_val = null
    )
    {
        if (!$columns)
            return;
        if(is_object($data)){
            $res = [];
            if (isset($data->id))
                $res['id'] = $data->id;
            foreach ($columns as $column => $det){
//            var_dump($det);
                if($det['type'] == "column"){
                    $res_value = $data->$column ?? null;
                    $json_res_value = json_decode($res_value);
                    if (is_array($json_res_value) || is_object($json_res_value)){//parse value if json
                        $res[$column] = $json_res_value;
                    }else{//else, return it raw
                        $res[$column] = $res_value;
                    }
                }elseif ($det['type'] == "service"){
                    $res[$column] = $this->getServiceResponse(
                        $column,
                        $det['service'],
                        $det,
                        $data,
                        $primary_key_val ?? $data->id ?? null,
                        $tbl_name
                    );
                }
            }
            $data = (object) $res;
        }else if (is_array($data)){
            $res = [];
            for ($i = 0; $i < count($data); $i ++){
                $_data = $data[$i];
                $obj = (object) [];
                if(isset($_data->id))
                    $obj->id = $_data->id;
                foreach ($columns as $column => $det){
//            var_dump($det);
                    if($det['type'] == "column"){
                        $res_value = $_data->$column ?? null;
                        $json_res_value = json_decode($res_value);
                        if (is_array($json_res_value) || is_object($json_res_value)){//parse value if json
                            $obj->$column = $json_res_value;
                        }else{//else, return it raw
                            $obj->$column = $res_value;
                        }
                    }elseif ($det['type'] == "service"){
                        $obj->$column = $this->getServiceResponse(
                            $column,
                            $det['service'],
                            $det,
                            $_data,
                            $primary_key_val ?? $_data->id ?? null,
                            $tbl_name
                        );
                    }
                }
                $res[] = $obj;
            }
            $data = $res;
        }

    }
    private function cleanUp(&$data,$model_name){
        unset($data->$model_name);
    }

}
