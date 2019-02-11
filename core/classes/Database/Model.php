<?php


namespace Data;
load_class([
    "Database/Connection",
    "Misc/Validator"
]);

use Path\Database\Connection\DB;
use Path\Database\Connection\Mysql;
use Path\DatabaseException;
use Path\FileSys;
use Path\Misc\Validator;

abstract class Model
{
    private   $conn;
    protected $table_name;
    private   $model_name;
    protected $primary_key     = "id";
    protected $updated_col     = "updated_at";
    protected $created_col     = "created_at";
    protected $record_per_page = 10;
    private   $query_structure = [
        "WITH"              => "",
        "SELECT"            => "",
        "AS"                => "",
        "JOIN"              => [],//table to join
        "ON"                => "",//associative array holding join condition
        "INSERT"            => "",
        "UPDATE"            => "",
        "WHERE"             => "",
        "GROUP_BY"          => "",
        "HAVING"            => "",
        "ORDER_BY"          => "",
        "LIMIT"             => ""
    ];

    public    $params       = [
        "SELECT"            => [],
        "WHERE"             => [],
        "UPDATE"            => [],
        "INSERT"            => [],
        "SORT"              => [],
        "LIMIT"             => []
    ];

    protected $writable_cols        = [];//writable columns(Can be overridden)
    protected $non_writable_cols    = [];//non writable (Can be overridden)

    protected $readable_cols        = [];//readable columns(Can be overridden)
    protected $non_readable_cols    = [];//non readable (Can be overridden)

    private   $writing              = [];//currently updated column and value
    private   $reading              = [];//currently updated column and value
    public    $last_insert_id;
    private   $table_cols;

    protected $fetch_method         = "FETCH_ASSOC";
    private   $pages                = [];
    private   $total_record;

    private   $validator;

    public function __construct()
    {
        $this->conn = (new Mysql())->connection;
        $this->table_cols = $this->getColumns($this->table_name);
        $this->model_name =  get_class($this);
    }
    private function getColumns($table){
        try{
            $q = $this->conn->query("DESCRIBE {$table}");
            $cols = [];
            foreach ($q as $k){
                $cols[] = $k["Field"];
            }
            return $cols;
        }catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }

    }

    /**
     * @param $col
     * @return boolean
     */
    private function is_valid_col($col):bool
    {
        return preg_match("/^[_\w\.]*$/",$col);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @throws DatabaseException
     */
    public function set($key, $value)
    {
        $class_name = get_class($this);
        if($this->isWritable($key)){
            $this->writing[$key] = $value;
        }else{
            throw new DatabaseException("can't write {$key} in {$class_name}");
        }
        return $this;
    }


    public function raw_select_query(){
        $query      = $this->buildWriteRawQuery("SELECT");
        return (object)[
            "query"     => $query,
            "params"    => [
                "select"=> $this->params["SELECT"],
                "where" => $this->params["WHERE"],
                "limit" => $this->params["LIMIT"]
            ]
        ];
    }
    /**
     * @param $table
     * @return $this
     */
    public function table(String $table){
        $this->table_name = $table;
        return $this;
    }
    private function where_gen(
        $conditions,
        $logic_gate = "AND"
    ){
        if(is_array($conditions)){
            $where = $this->query_structure["WHERE"];
            $str   = "";

            foreach($conditions as $condition => $value){
                $str .= " {$condition} = ? {$logic_gate} ";
                $this->params["WHERE"][] = $value;
            }
//            Remove trailing "AND"
            $str = preg_replace("/($logic_gate)\s*$/","",$str);
            if(!$this->query_structure["WHERE"]){
//                If no WHERE Clause already specified, add new one
                @$this->query_structure["WHERE"] = $str;
            }else{
//                if there is already a WHERE clause, join with AND
                $this->query_structure["WHERE"] .= " {$logic_gate} ". $str;
            }
        }else if(preg_match("/\w+\s*[><=!]+\s*\w+/",$conditions)){
            $str   = "";
//          if conditions are in raw string
            $split = explode(",",$conditions);
            foreach ($split as $val){
                preg_match("/(\w+)\s*([><=!]*)\s*(\w+)/",$val,$matches);
                $str .= "{$matches[1]} {$matches[2]} ? {$logic_gate} ";
                $this->params["WHERE"][] = $matches[3];
            }
//            Remove trailing "AND"
            $str = preg_replace("/(".$logic_gate.")\s*$/","",$str);
            if(!$this->query_structure["WHERE"]){
                @$this->query_structure["WHERE"] = $str;
            }else{
                $this->query_structure["WHERE"] .= " {$logic_gate} ". $str;
            }
        }elseif(preg_match("/^[_\w\.|\s\(\)\`\\'\",]*$/",$conditions)){
            if(!$this->query_structure["WHERE"]){
                @$this->query_structure["WHERE"] = $conditions;
            }else{
                $this->query_structure["WHERE"] .= " {$logic_gate} ". $conditions;
            }
        }else{
            throw new DatabaseException("Invalid WHERE condition");
        }
    }

    /**
     * @param $key
     * @return bool
     */
    private function isWritable($key){
        if(in_array(trim($key),$this->writable_cols) || !in_array(trim($key),$this->non_writable_cols)){
            return true;
        }
        return false;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function writable(array $columns){
        $this->writable_cols = array_merge($this->writable_cols,$columns);
        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function nonWritable(array $columns){
        $this->non_writable_cols = array_merge($this->non_writable_cols,$columns);
        return $this;
    }
    /**
     * @param array $columns
     * @return $this
     */
    public function readable(array $columns){
        $this->readable_cols = array_merge($this->readable_cols,$columns);
        return $this;
    }
    /**
     * @param array $columns
     * @return $this
     */
    public function nonReadable(array $columns){
        $this->non_readable_cols = array_merge($this->non_readable_cols,$columns);
        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    private function isReadable($key){
        if(in_array(trim($key),$this->readable_cols) || !in_array(trim($key),$this->non_readable_cols)){
            return true;
        }
        return false;
    }
    private function filterNonWritable(Array $data){
        foreach ($data as $key => $value){
            if(!$this->isWritable($key))
                unset($data[$key]);
            if(!in_array($key,$this->table_cols))
                unset($data[$key]);
        }
        return $data;
    }
    private function filterNonReadable(Array $data){
        foreach ($data as $index => $key){
            if(!$this->isReadable($key)){
                unset($data[$index]);
            }
        }
        return $data;
    }

    /**
     * @param array $data
     * @param string $type
     */
    private function rawKeyValueBind(Array $data, $type = "UPDATE"){
        $string = "";
        foreach($data as $column => $value){
            if(@$this->query_structure[$type]){
                $string .= ",{$column} = ?,";
                $this->params[$type][] = $value;
            }else{
                $string .= "{$column} = ?,";
                $this->params[$type][] = $value;
            }

        }
        $string = preg_replace("/,\s*$/","",$string);//remove trailing comma

        @$this->query_structure[$type] .= $string;
    }
    /**
     * @param $conditions
     * @return $this
     */
    public function where(
        $conditions
    ){
        $this->where_gen($conditions,"AND");
        return $this;
    }
    public function rawWhere(
        $where,
        $params = null
    ){
        $this->params["WHERE"] = array_merge($this->params["WHERE"],$params);
        $this->query_structure["WHERE"] .= $where;
        return $this;
    }
    public function identify(
        $id = false
    ){
        if(!$this->primary_key)
            throw new DatabaseException("specify primary key in {$this->model_name}");
        if($id === false)
            throw new DatabaseException("specify id in identify method of \"{$this->model_name}\"");

        $this->where_gen([$this->primary_key => $id],"AND");
        return $this;
    }

    public function orWhere(
        $conditions,
        $params = null
    ){
        $this->where_gen($conditions,"OR");
        return $this;
    }
    private function rawColumnGen($cols){
        if($this->query_structure["SELECT"]){
            $this->query_structure["SELECT"] .= ",".join(",",$cols);
        }else{
            $this->query_structure["SELECT"] = join(",",$cols);
        }
    }
    private function buildWriteRawQuery($command = "UPDATE"){
        switch ($command){
            case "UPDATE":
                $params     = $this->query_structure[$command];
                $command    = "UPDATE ".$this->table_name." SET ";
                $query      = $command.$params;
                if($this->query_structure["WHERE"])
                    $query .= " WHERE ".$this->query_structure["WHERE"];
                break;
            case "INSERT":
                $params     = $this->query_structure[$command];
                $command    = "INSERT INTO {$this->table_name} SET {$params}";
                $query      = $command;
                break;
            case "DELETE":
                $query      = "DELETE FROM {$this->table_name} ";

                if($this->query_structure["WHERE"])
                    $query .= PHP_EOL." WHERE ".$this->query_structure["WHERE"];
                break;
            case "SELECT":
                $params     = $this->query_structure["SELECT"];
                $query      = "SELECT SQL_CALC_FOUND_ROWS {$params}";
                $query     .= PHP_EOL." FROM {$this->table_name} ";
                if($this->query_structure["JOIN"]){
                    $query .= " ".$this->rawJoinGen($this->query_structure["JOIN"]);
                }
                if(@$this->query_structure["WHERE"])
                    $query .= PHP_EOL." WHERE ".$this->query_structure["WHERE"];                       if(@$this->query_structure["GROUP_BY"])
                $query .= PHP_EOL." GROUP BY ".$this->query_structure["GROUP_BY"];
                if(@$this->query_structure['ORDER_BY'])
                    $query .= PHP_EOL." ORDER BY ".$this->query_structure['ORDER_BY'];
                if(@$this->query_structure['LIMIT'])
                    $query .= PHP_EOL." LIMIT ".$this->query_structure['LIMIT'];
                break;
            case "SORT":
                $params     = $this->query_structure["SELECT"];
                $query      = "SELECT {$params} FROM {$this->table_name} ";
                if($this->query_structure["WHERE"])
                    $query .= " WHERE ".$this->query_structure["WHERE"];
                break;
            default:
                return false;
        }

//echo  $query;
        return $query;
    }

    /**
     * @return array
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    private function compileData($data){

    }

    /**
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator){
        $validator->model = $this;
        $this->validator = $validator;
        return $this;
    }
    public function update(array $data = null){
        if(!$data)
            $data = $this->filterNonWritable($this->writing);
        elseif($data AND is_array($data))
            $data = array_merge($this->filterNonWritable($data),$this->writing);

        if(!$data)
            throw new DatabaseException("Error Attempting to update Empty data set");
        if(!$this->table_name)
            throw new DatabaseException("No Database table name specified, Configure Your model or  ");

        if($this->validator && $this->validator->hasError()){
            return false;
        }

//        GET and set raw query from array
        $this->rawKeyValueBind($data,"UPDATE");
//        Process and execute query

        $query      = $this->buildWriteRawQuery("UPDATE");
        $params     = array_merge($this->params["UPDATE"],$this->params["WHERE"]);
//        var_dump($params);
//        echo PHP_EOL.$query;
        try{
            $prepare    = $this->conn->prepare($query);//Prepare query\
            $prepare    ->execute($params);
        }catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }

        return $this;
    }
    public function insert(array $data = null){
        if(!$data)
            $data = $this->writing;
        elseif($data AND is_array($data))
            $data = array_merge($this->filterNonWritable($data),$this->writing);

        if(!$data)
            throw new DatabaseException("Error Attempting to update Empty data set");
        if(!$this->table_name)
            throw new DatabaseException("No Database table name specified, Configure Your model or  ");

//        GET and set raw query from array
        $this->rawKeyValueBind($data,"INSERT");
//        Process and execute query

        $query      = $this->buildWriteRawQuery("INSERT");
        $params     = $this->params["INSERT"];
//        var_dump($params);
//        echo PHP_EOL.$query;

        try{
            $prepare    = $this->conn->prepare($query);//Prepare query\
            $prepare    ->execute($params);
            $this       ->last_insert_id = $this->conn->lastInsertId();
        }catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }
        return $this;
    }
    public function delete(){
        if(!$this->table_name)
            throw new DatabaseException("No Database table name specified, Configure Your model or  ");

        $query = $this->buildWriteRawQuery("DELETE");
        $params = $this->params["WHERE"];
//        var_dump($params);
//        echo PHP_EOL.$query;
        try{
            $prepare    = $this->conn->prepare($query);//Prepare query\
            $prepare    ->execute($params);
        }catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }
        return $this;
    }

    /**
     * @param array $cols
     * @param bool $sing_record
     * @return array|mixed
     * @throws DatabaseException
     */
    public function all(
        $cols = [],
        $sing_record = false
    ){
        if(!is_array($cols) && is_string($cols))
            $cols = explode(",",$cols);
        if(is_array($cols)){
            if(!$cols)
                $cols = $this->filterNonReadable($this->table_cols);

            $cols = $this->filterNonReadable($cols);

            if(!$cols)
                throw new DatabaseException("Error Attempting to update Empty data set");
            if(!$this->table_name)
                throw new DatabaseException("No Database table name specified, Configure Your model or  ");

            $this->rawColumnGen($cols);
        }
        $query      = $this->buildWriteRawQuery("SELECT");
        $params     = array_merge($this->params["SELECT"],$this->params["WHERE"],$this->params["LIMIT"]);

//        var_dump($params);
//        echo "<br>".$query."<br>";
        try{
            $prepare                = $this->conn->prepare($query);//Prepare query\
            $prepare                ->execute($params);
            $this->total_record     = $this->conn->query("SELECT FOUND_ROWS()")->fetchColumn();
            if($sing_record)
                return $prepare->fetch(constant("\PDO::{$this->fetch_method}"));
            else
                return $prepare->fetchAll(constant("\PDO::{$this->fetch_method}"));
        }catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }
    }


    /**
     * @param bool $sing_record
     * @return array|mixed
     * @throws DatabaseException
     * @internal param array $cols
     */
    public function get(
        $sing_record = false
    ){

        $query      = $this->buildWriteRawQuery("SELECT");
        $params     = array_merge($this->params["SELECT"],$this->params["WHERE"],$this->params["LIMIT"]);

        print_r($params);
        echo "<br>".$query."<br>";
        try{
            $prepare                = $this->conn->prepare($query);//Prepare query\
            $prepare                ->execute($params);
            $this->total_record     = $this->conn->query("SELECT FOUND_ROWS()")->fetchColumn();
            if($sing_record)
                return $prepare->fetch(constant("\PDO::{$this->fetch_method}"));
            else
                return $prepare->fetchAll(constant("\PDO::{$this->fetch_method}"));
        }catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }
    }
    /**
     * @param int $_from
     * @param int $_to
     * @return $this
     */
    public function batch($_from = 0, $_to = 10){
        $this->query_structure["LIMIT"] = "?,?";
        $this->params["LIMIT"]          = [$_from,$_to];
        return $this;
    }

    public function paginate($page = 1){
//        get total record
        $page -= 1;
        $total_records = $this->conn->query("SELECT SQL_CALC_FOUND_ROWS id FROM {$this->table_name} LIMIT 0,1");
        $total_records = $this->conn->query("SELECT FOUND_ROWS()")->fetchColumn();

        $offset =  $this->record_per_page * $page;
        $total = $this->record_per_page;
//        generate available pages
        $current_page = 0;
        while(($current_page + $this->record_per_page-1) < $total_records){
            $this->pages[] = [
                "page_number"   => $current_page+1,
                "navigable"     => ($current_page != $page)
            ];
            $current_page ++;
        }
        $this->query_structure["LIMIT"] = "?,?";
        $this->params["LIMIT"]          = [$offset,$total];
        return $this;
    }

    public function sortBy($sort){
        if(is_array($sort)){
            $str = "";
            foreach ($sort as $key => $val){
                $str .=" {$key} {$val},";
            }
            $str = preg_replace("/,$/","",$str);
            if($this->query_structure["ORDER_BY"]){
                $this->query_structure["ORDER_BY"] .= ", ".$str;
            }else{
                $this->query_structure["ORDER_BY"] = $str;
            }
        }else{
            if($this->query_structure["ORDER_BY"]){
                $this->query_structure["ORDER_BY"] .= ", ".$sort;
            }else{
                $this->query_structure["ORDER_BY"] = $sort;
            }
        }
        return $this;
    }
    public function like($wild_card){
        if(!$this->query_structure["WHERE"])
            throw new DatabaseException("WHERE Clause is empty");

        $this->query_structure["WHERE"] .= " LIKE ?";
        $this->params["WHERE"][] = "$wild_card";
        return $this;
    }
    public function notLike($wild_card){
        if(!$this->query_structure["WHERE"])
            throw new DatabaseException("WHERE Clause is empty");

        $this->query_structure["WHERE"] .= " NOT LIKE ?";
        $this->params["WHERE"][] = "$wild_card";
        return $this;
    }
    public function between($start,$stop){
        if(!$this->query_structure["WHERE"])
            throw new DatabaseException("WHERE Clause is empty");

        $this->query_structure["WHERE"] .= " BETWEEN ? AND ?";
        $this->params[] = $start;
        $this->params[] = $stop;
        return $this;
    }

    private function getOn($arr){
        foreach ($arr as $key => $value) {
            return $key." = ".$value;
        }
        return "";
    }


    public function rawJoinGen($table_joins){
        $str = "";
        foreach ($table_joins as $table => $value) {
            $type = $value["type"];
            $on   = $value["on"];
            $str .= PHP_EOL." ".$type." ".$table." ".PHP_EOL."   "."ON"." ".$on;
        }
        return $str;
    }
    /**
     * @param string $type
     * @param $table
     * @param $on
     * @return $this
     */
    private function join($type = "INNER JOIN", $table, $on){
        $this->query_structure["JOIN"][$table]["type"] =  $type." JOIN";
        $this->query_structure["JOIN"][$table]["on"] =  $this ->getOn($on);
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function leftJoin($table, $on){
        $this->join("LEFT",$table,$on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function innerJoin($table, $on){
        $this->join("INNER",$table,$on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function rightJoin($table, $on){
        $this->join("RIGHT",$table,$on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function fullJoin($table, $on){
        $this->join("FULL",$table,$on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param array $cols
     * @return object
     */
    public function first($cols = []):object {
        $this->query_structure["ORDER_BY"] = "{$this->primary_key} ASC";
        $this->query_structure["LIMIT"]    = "0,1";
        return (object)$this->all($cols,true);
    }

    /**
     * @param array $cols
     * @return object
     */
    public function last($cols = []):object {
        $this->query_structure["ORDER_BY"] = "{$this->primary_key} DESC";
        $this->query_structure["LIMIT"]    = "0,1";
        return (object)$this->all($cols,true);
    }

    /**
     * @return mixed
     */
    public function count(){
        $this->query_structure["SELECT"] = "COUNT({$this->primary_key}) as total";
        $this->groupBy($this->primary_key);
        return $this->all(null,true)["total"];
    }

    /**
     * @param $col
     * @return $this
     */
    public function max($col){
        $this->query_structure["ORDER_BY"] = "{$col} DESC";
        $this->query_structure["LIMIT"]    = "0,1";
        return $this;
    }

    /**
     * @param $col
     * @return $this
     */
    public function min($col){
        $this->query_structure["ORDER_BY"] = "{$col} ASC";
        $this->query_structure["LIMIT"]    = "0,1";
        return $this;
    }
    public function select($columns,$params = []){
        if($columns instanceof Model){
            $raw = $columns->raw_select_query();
            if($this->query_structure["SELECT"]){
                $this->query_structure["SELECT"] .= ", (".str_replace("SQL_CALC_FOUND_ROWS","",$raw->query).")";
                $this->params["SELECT"] = array_merge($this->params["SELECT"],$raw->params["select"]);
                $this->params["WHERE"] = array_merge($this->params["WHERE"],$raw->params["where"]);
                $this->params["LIMIT"] = array_merge($this->params["LIMIT"],$raw->params["limit"]);
            }else{
                $this->query_structure["SELECT"] = " (".str_replace("SQL_CALC_FOUND_ROWS","",$raw->query).")";
                $this->params["SELECT"] = array_merge($this->params["SELECT"],$raw->params["select"]);
                $this->params["WHERE"] = array_merge($this->params["WHERE"],$raw->params["where"]);
                $this->params["LIMIT"] = array_merge($this->params["LIMIT"],$raw->params["limit"]);
            }
        }else{
            if(is_array($columns)){
                if(!$columns)
                    $columns = $this->filterNonReadable($this->table_cols);

                if(!$columns)
                    throw new DatabaseException("Error Attempting to fetch Empty column set");
                if(!$this->table_name)
                    throw new DatabaseException("No Database table name specified, Configure Your model or  ");
                $columns = $this->filterNonReadable($columns);
                if(!$columns)
                    throw new DatabaseException("Can't read empty sets of columns in \"select()\" Method ");

                $this->rawColumnGen($columns);
            }else{
                if($this->query_structure["SELECT"]){
                    $this->query_structure["SELECT"] .=", ".$columns;
                }else{
                    $this->query_structure["SELECT"] = $columns;
                }
            }
        }
        if($params)
            $this->params["SELECT"] = array_merge($this->params["SELECT"],$params);
        return $this;
    }

    /**
     * @param $alias
     * @return $this
     */
    public function as($alias){
        if($this->query_structure["SELECT"]){
            $this->query_structure["SELECT"] .= " AS ".$alias." ";
        }
        return $this;
    }
    public function groupBy($col){
        if(is_array($col)){
            $this->query_structure["GROUP_BY"] = join(",",$col);
        }else{
            if(!$this->is_valid_col($col))
                throw new DatabaseException("Invalid Column name \"{$col}\" in \"groupBy()\" method ");

            $this->query_structure["GROUP_BY"] = $col;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function exists():bool {
        if(strlen(trim($this->query_structure["SELECT"])) > 0){
            $this->query_structure["SELECT"] .= ", COUNT({$this->primary_key}) as total";
        }else{
            $this->query_structure["SELECT"] .= " COUNT({$this->primary_key}) as total";
        }
        return $this->all(null,true)["total"] > 0;
    }
    /**
     * @return bool
     */
    public function doesntExists():bool {
        if(strlen(trim($this->query_structure["SELECT"])) > 0){
            $this->query_structure["SELECT"] .= ", COUNT({$this->primary_key}) as total";
        }else{
            $this->query_structure["SELECT"] .= " COUNT({$this->primary_key}) as total";
        }
        return $this->all(null,true)["total"] < 1;
    }

    /**
     * @param array ...$cols
     * @return $this
     */
    public function whereCols(...$cols){
        $this->where_gen("MATCH( ".join(",",$cols)." )");
        return $this;
    }

    /**
     * @param $value
     * @param string $mode
     * @return  $this
     */
    public function matches($value, $mode = "BOOLEAN"){
        $this->query_structure["WHERE"] .= " AGAINST(? IN {$mode} MODE)";
        $this->params["WHERE"][]         = $value;
        return $this;
    }


}