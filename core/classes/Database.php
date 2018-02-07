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
    public function Select($columns){
        $this->query_data = [];
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
    public function _As($alias){
        $column = $this->query_data["column"];
        $this->query_data["column"] = "{$column} AS {$alias}";
        return $this;
    }
    public function From($table){
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
public function Where($conditions){
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

        }elseif(preg_match('/^[_\w]*$/',$conditions)){
            $this->query_data['where'] = $conditions;
        }
        return $this;
    }
    public function orWhere($conditions){
        if(empty($this->query_data['where'])){
            throw new \Exception('User the where method before the or_where');
        }
        if(is_array($conditions)){
            $string = "";


            foreach($conditions as $condition => $value){
                $string .= " OR {$condition} = ? ";
                $this->query_data['bind_data'][] = $value;
            }
//            $string = preg_replace("/(AND)\s*$/","",$string);

                $this->query_data['where'] .= $string;
        }else if(preg_match('/^[_\w]*$/',$conditions)){
            $where = $this->query_data['where'];
            $this->query_data['where'] = "{$where} OR {$conditions}";
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

        }else{
            throw new \Exception("Invalid value for 'orWhere' method");
        }
        return $this;
    }
public function Like($compare){
    $where = $this->query_data['where'];
    $this->query_data['where'] = "{$where} LIKE ?";
    $this->query_data['bind_data'][] = "%{$compare}%";
    return $this;
}
    public function notLike($compare){
        $where = $this->query_data['where'];
        $this->query_data['where'] = "{$where} NOT LIKE ?";
        $this->query_data['bind_data'][] = "%{$compare}%";
        return $this;
    }
public function Between($start,$stop){
        $where = $this->query_data['where'];
        $this->query_data['where'] = "{$where} BETWEEN ? AND ?";
        $this->query_data['bind_data'][] = $start;
        $this->query_data['bind_data'][] = $stop;
        return $this;
    }
public function notBetween($start,$stop){
        $where = $this->query_data['where'];
        $this->query_data['where'] = "{$where} NOT BETWEEN ? AND ?";
        $this->query_data['bind_data'][] = $start;
        $this->query_data['bind_data'][] = $stop;

        return $this;
    }
public function Update($table){
    $this->query_data = [];
    if(func_num_args() < 1 || is_null($table) || empty($table)){
        throw new \Exception("Specify table to update");
    }
    $this->query_data['table'] = $table;
    $this->query_data['command'] = "UPDATE";

    return $this;
}



public function Set($values){
    if(func_num_args() < 1 || is_null($values) || empty($values)){
        throw new \Exception('Set value to update');
    }

    if(is_array($values)){
        $string = "";


        foreach($values as $column => $value){
            if(@$this->query_data['update_data']){
                $string .= ",{$column} = ?,";
                $this->query_data['bind_data'][] = $value;
            }else{
                $string .= "{$column} = ?,";
                $this->query_data['bind_data'][] = $value;
            }

        }
            $string = preg_replace("/,\s*$/","",$string);//remove trailing commar

        @$this->query_data['update_data'] .= $string;

    }else if(preg_match("/\w+\s*[><=!]\s*\w+/",$values)){

        $split = explode(",",$values);
        $string = "";
        foreach ($split as $val){
            $match = preg_match("/(\w+)\s*([><=!])\s*(\w+)/",$val,$matches);
            if(!empty($this->query_data['update_data'])){
                $string .= ",{$matches[1]} = ?,";
                $this->query_data['bind_data'][] = $matches[3];
            }else{
                $string .= "{$matches[1]} = ?,";
                $this->query_data['bind_data'][] = $matches[3];
            }

        }
        $string = preg_replace("/,\s*$/","",$string);

        @$this->query_data['update_data'] .= $string;

    }
    return $this;
}
public function Insert($data){
    $this->query_data = [];
    if(func_num_args() < 1 || is_null($data) || empty($data)){
        throw new \Exception('Set value to update');
    }

    if(is_array($data)){
        $string = "";
        foreach($data as $column => $value){
            if(@$this->query_data['update_data']){
                $string .= ",{$column} = ?,";
                $this->query_data['bind_data'][] = $value;
            }else{
                $string .= "{$column} = ?,";
                $this->query_data['bind_data'][] = $value;
            }

        }
        $string = preg_replace("/,\s*$/","",$string);//remove trailing commar

        @$this->query_data['update_data'] .= $string;

    }else if(preg_match("/\w+\s*[><=!]\s*\w+/",$data)){

        $split = explode(",",$data);
        $string = "";
        foreach ($split as $val){
            $match = preg_match("/(\w+)\s*([><=!])\s*(\w+)/",$val,$matches);
            if(!empty($this->query_data['update_data'])){
                $string .= ",{$matches[1]} = ?,";
                $this->query_data['bind_data'][] = $matches[3];
            }else{
                $string .= "{$matches[1]} = ?,";
                $this->query_data['bind_data'][] = $matches[3];
            }

        }
        $string = preg_replace("/,\s*$/","",$string);

        @$this->query_data['update_data'] .= $string;

    }
    return $this;
}
public function removeTrailingComma($str){
    $str = preg_replace("/,$/","",$str);
    return $str;
}
public function Into($table){
    if(func_num_args() < 1 || is_null($table) || empty($table)){
        throw new \Exception("Specify table to update");
    }
    $this->query_data['table'] = $table;
    $this->query_data['command'] = "INSERT INTO";
    return $this;
}
public function deleteFrom($table){
    if(func_num_args() < 1 || is_null($table) || empty($table)){
        throw new \Exception("Specify table to delete from");
    }
    $this->query_data['table'] = $table;
    $this->query_data['command'] = "DELETE FROM ";
    return $this;
}
public function Exe(){
    $query = "{$this->query_data['command']} {$this->query_data['table']}";

    if(@$this->query_data['update_data']){
        $query .= " SET {$this->query_data['update_data']}";
    }


    if(@$this->query_data['where']){
        $query .= " WHERE {$this->query_data['where']}";
    }
    //echo $query.'<br><br>';
    try{
        $exe = $this->db_con->prepare($query);
        $exe->execute(@$this->query_data['bind_data']);
        //echo "Query successfully executed";
    }catch (\PDOException $e){
        throw new \Exception($e->getMessage());
    }

    return true;
}
public function Execute(){
    $this->Exe();
}

    /**
     * @return array
     */
public function orderBy($sort){
    if(is_array($sort)){
        $str = "";
        foreach ($sort as $key => $val){
            $str .=" {$key} {$val},";
        }
        $str = $this->removeTrailingComma($str);
        $this->query_data['sort'] = " ORDER BY ".$str;
    }else{
        $this->query_data['sort'] = " ORDER BY ".$sort;
    }
    return $this;
}
public function Get($is_array = true){
        $query = "";
        $query = "SELECT {$this->query_data['column']} FROM {$this->query_data['table']}";
        if(@$this->query_data['where']){
            $query .= " WHERE {$this->query_data['where']}";
        }
        if(@$this->query_data['sort']){
            $query .= $this->query_data['sort'];
        }

        try{
//echo $query;
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
        return $this;
    }

}

?>
