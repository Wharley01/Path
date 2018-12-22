<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/5/2018
 * @Time 10:32 PM
 * @Project Path
 */

namespace Path\Database;
load_class("Database/Structure");
load_class("Database/Connection");


use Path\Database\Connection\Mysql;
use Data\Database;
use Path\Database\Structure;
use Path\DataStructureException;

class Prototype
{
    private $db_conn;
    public function __construct()
    {
        $this->db_conn = (new Mysql())->connection;
    }
    public function create(string $table,callable $structure){
        $proto = new Structure($table);
        $proto->action = "creating";

        $structure($proto);
        echo $proto->getRawQuery();
    }
    public function alter(string $table,callable $structure){
        $proto = new Structure($table);
        $proto->action = "altering";

        $structure($proto);
        echo $proto->getRawQuery();
    }

    public function drop(...$tables){
        $tables = join(",",$tables);
        try{
            $query = $this->db_conn->query("DROP TABLE IF EXISTS {$tables}");
        }catch (\Exception $e){
            throw new DataStructureException($e->getMessage());
        }
        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws DataStructureException
     */
    public function truncate($table){
        try{
            $query = $this->db_conn->query("TRUNCATE TABLE `{$table}`");
        }catch (\Exception $e){
            throw new DataStructureException($e->getMessage());
        }
        return $this;
    }
}