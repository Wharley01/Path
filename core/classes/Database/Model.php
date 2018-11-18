<?php


namespace Data;
load_class("Database/Connection");

use Connection\DB;
use Connection\Mysql;
use Path\DatabaseException;

abstract class Model
{
    private   $conn;
    protected $table_name;
    protected $primary_key = "id";
    protected $updated_col = "updated_at";
    protected $created_col = "created_at";

    private $query_structure = [
        "WITH"              => "",
        "SELECT"            => "",
        "JOIN"              => "",//table to join
        "JOIN_TYPE"         => "",
        "ON"                => "",//associative array holding join condition
        "INTO"              => "",
        "WHERE"             => "",
        "GROUP_BY"          => "",
        "HAVING"            => "",
        "ORDER BY"          => "",
        "LIMIT"             => ""
    ];

    public    $params           = [];
    private   $raw_query        = "";
    protected $writable_cols    = [];//writable columns(Can be overridden)
    protected $forbidden_cols   = [];//non writable (Can be overridden)

    public    $writing             = [];//currently updated column and value

    public function __construct()
    {
        $this->conn = new Mysql();
    }
    public function __set($name, $value)
    {
        $class_name = get_class($this);
        if($this->writable_cols && !in_array($name,$this->writable_cols))
            throw new DatabaseException("\"{$name}\" column is not writable in {$class_name}");

        if($this->forbidden_cols && in_array($name,$this->forbidden_cols))
            throw new DatabaseException("\"{$name}\" column is not writable in {$class_name}");

        $this->writing[$name] = $value;
        // TODO: Implement __set() method.
    }


    public function raw_str(){

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
        $type = "AND"
    ){
        if(is_array($conditions)){
            $where = $this->query_structure["WHERE"];

            foreach($conditions as $condition => $value){
                $where .= " {$condition} = ? {$type} ";
                $this->params[] = $value;
            }
//            Remove trailing "AND"
            $where = preg_replace("/(AND)\s*$/","",$where);
            if(!isset($this->query_structure["WHERE"])){
//                If no WHERE Clause already specified, add new one
                @$this->query_structure["WHERE"] = $where;
            }else{
//                if there is already a WHERE clause, join with AND
                $this->query_structure["WHERE"] .= " {$type} ". $where;
            }
        }else if(preg_match("/\w+\s*[><=!]\s*\w+/",$conditions)){
//          if conditions are in raw string
            $split = explode(",",$conditions);
            $string = $this->query_structure["WHERE"];
            foreach ($split as $val){
                preg_match("/(\w+)\s*([><=!]*)\s*(\w+)/",$val,$matches);
                $string .= "{$matches[1]} {$matches[2]} ? {$type} ";
                $this->params[] = $matches[3];
            }
//            Remove trailing "AND"
            $string = preg_replace("/(AND)\s*$/","",$string);
            if(!isset($this->query_structure["WHERE"])){
                @$this->query_structure["WHERE"] = $string;
            }else{
                $this->query_structure["WHERE"] .= " {$type} ". $string;
            }
        }elseif(preg_match('/^[_\w]*$/',$conditions)){
            $this->query_structure["WHERE"] = $conditions;
        }else{
            throw new DatabaseException("Invalid WHERE condition");
        }
    }

    /**
     * @param $key
     * @return bool
     */
    private function isEligible($key){
        if($this->writable_cols && !in_array($key,$this->writable_cols))
            return false;

        if($this->forbidden_cols && in_array($key,$this->forbidden_cols))
            return false;

        return true;
    }
    private function filterIneligible(Array $data){
        foreach ($data as $key => $value){
            if(!$this->isEligible($key))
                unset($data[$key]);
        }
        return $data;
    }
    public function where(
        $conditions,
        $params = null
    ){
        $this->where_gen($conditions,"AND");
        return $this;
    }
    public function orWhere(
        $conditions,
        $params = null
    ){
        $this->where_gen($conditions,"OR");
        return $this;
    }
    public function when(){
        return $this;
    }

    public function update(array $data){
        if(!$data)
            $data = $this->writing;
        else
            $data = $this->filterIneligible($data);



        return $this;
    }
    public function select($cols,$bind_data = null){
        return $this;
    }
    public function like($wild_card){
        if(!$this->query_structure["WHERE"])
            throw new DatabaseException("WHERE Clause is empty");

        $this->query_structure["WHERE"] .= " LIKE ?";
        $this->params[] = "$wild_card";
        return $this;
    }
    public function notLike($wild_card){
        if(!$this->query_structure["WHERE"])
            throw new DatabaseException("WHERE Clause is empty");

        $this->query_structure["WHERE"] .= " NOT LIKE ?";
        $this->params[] = "$wild_card";
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
    public function join($table,$on){
        return $this;
    }
    public function all(){
        return $this;
    }
    public function get(){
        return $this;
    }
    public function first(){
        return $this;
    }
    public function just(){
        return $this;
    }
    public function orderBy(){
        return $this;
    }
    public function chunk($start,$stop){
        return $this;
    }
    public function count(){
        return $this;
    }
    public function max($col){
        return $this;
    }
    public function min($col){
        return $this;
    }
    public function groupBy($col){
        return $this;
    }

    public function exists(){
        return $this;
    }
    public function doesntExists(){
        return $this;
    }







}