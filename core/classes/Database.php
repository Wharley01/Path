<?php
namespace Data;
use Connection\DB;
class Database{
    private $connection;
    private $query;
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
        $this->query = "SELECT {$string} FROM ";
        return $this;

    }
    public function from($table){
        if(empty($this->query)){
            throw new \Exception("Specify Column");
        }
        $query = $this->query . $table;
    }


}




?>
