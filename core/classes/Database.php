<?php
namespace Data;
use Connection\DB;
class Database{
    private $db_con;
    public $query_data = array();
    public function __construct(DB $connection)
    {
        $this->db_con = $connection->__construct();
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
                        $string .= " {$condition} = ? AND ";
                        $this->query_data['bind_data'][] = $value;
                }
                $string = preg_replace("/(AND)\s*$/","",$string);
            if(!isset($this->query_data['where'])){
                @$this->query_data['where'] .= $string;
            }else{
                $this->query_data['where'] .= ' AND '. $string;
            }


        }else if(preg_match("/\w+\s*[><=!]\s*\w+/",$conditions)){

            $split = explode(",",$conditions);
            $string = "";
            foreach ($split as $val){
                $match = preg_match("/(\w+)\s*([><=!])\s*(\w+)/",$val,$matches);
                $string .= "{$matches[1]} {$matches[2]} ? AND ";
                $this->query_data['bind_data'][] = $matches[3];
            }
            $string = preg_replace("/(AND)\s*$/","",$string);
            if(!isset($this->query_data['where'])){
                @$this->query_data['where'] .= $string;
            }else{
                $this->query_data['where'] .= ' AND '. $string;
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
                $string .= " OR {$condition} = ? ";
                $this->query_data['bind_data'][] = $value;
            }
            $string = preg_replace("/(AND)\s*$/","",$string);

                $this->query_data['where'] .= $string;



        }else if(preg_match("/\w+\s*[><=!]\s*\w+/",$conditions)){

            $split = explode(",",$conditions);
            $string = "";
            foreach ($split as $val){
                $match = preg_match("/(\w+)\s*([><=!])\s*(\w+)/",$val,$matches);
                $string .= "OR {$matches[1]} {$matches[2]} ? ";
                $this->query_data['bind_data'][] = $matches[3];
            }
            $string = preg_replace("/(OR)\s*$/","",$string);

                $this->query_data['where'] .= $string;

        }
        return $this;
    }
public function getResult($is_array = true){
        $query = "";
        $query = "SELECT {$this->query_data['column']} FROM {$this->query_data['table']}";
        if(@$this->query_data['where']){
        $query .= " WHERE {$this->query_data['where']}";
                }
        try{

            $exe = $this->db_con->prepare($query);
            $exe->execute(@$this->query_data['bind_data']);

            if($is_array){
                return $exe->fetchAll(\PDO::FETCH_ASSOC);
            }else{
                return (object) $exe->fetchAll(\PDO::FETCH_ASSOC);
            }



        }catch (\PDOException $e){
            echo $e->getMessage();
        }
}

}




?>
