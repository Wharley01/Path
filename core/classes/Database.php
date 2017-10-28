<?php
namespace Data;
use Connection\DB;
class Database{
    private $connection;
    public $query_data = array();
    public function __construct(DB $connection)
    {
        $this->connection = $connection->__construct();
    }

    /**
     * @param $columns
     */
    public function select($columns){
        $string = "";
        if(empty($columns) || func_num_args() < 1){
            throw new \Exception("Provide Column/Columns");
        }
        if(is_array($columns)){
            foreach ($columns as $column){
                $string .= $column.",";
            }
            //remove trailing comma
            $string = preg_replace("/,$/","",$string);
        }else{
            $string = $columns;
        }
        $this->query_data["column"] = $string;
        return $this;

    }
    public function from($table){
        if(empty($this->query_data)){
            throw new \Exception("Specify Column");
        }
        $this->query_data["table"] = $table;
        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function where($conditions){
        if(func_num_args() < 1){
            return $this;
        }
        if(is_array($conditions)){
            $string = "";


                foreach($conditions as $condition => $value){
                        $string .= " {$condition} = {$value} AND ";
                }
                $string = preg_replace("/(AND)\s*$/","",$string);
            if(!isset($this->query_data['where'])){
                $this->query_data['where'] .= $string;
            }else{
                $this->query_data['where'] .= ' AND '. $string;
            }


        }else if(preg_match("/\w+\s*[><=!]\s*\w+/",$conditions)){
            $split = explode(",",$conditions);
            foreach ($split as $val){
                if(!isset($this->query_data['where'])){
                    $this->query_data['where'] .= $val;
                }else{
                    $this->query_data['where'] .= ' AND '. $val;
                }
            }

        }
        return $this;
    }
    public function or_where($conditions){
        if(empty($this->query_data['where'])){
            throw new \Exception('User the where method before the or_where');
        }
        if(is_array($conditions)){
            $string = "";


            foreach($conditions as $condition => $value){
                $this->query_data['where'] .= " OR {$condition} = {$value}";
            }
////            $string = preg_replace("/(AND)\s*$/","",$string);
//            if(!isset($this->query_data['where'])){
//                $this->query_data['where'] .= $string;
//            }else{
//                $this->query_data['where'] .= ' AND '. $string;
//            }


        }else if(preg_match("/\w+\s*[><=!]\s*\w+/",$conditions)){
            $split = explode(",",$conditions);
            foreach ($split as $val){

                    $this->query_data['where'] .= ' OR '. $val;

            }

        }
        return $this;

    }


}




?>
