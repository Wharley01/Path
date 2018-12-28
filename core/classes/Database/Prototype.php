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

//        Add extra setup column
        $proto->column("is_deleted")
            ->type("boolean")
            ->default(0);

        $proto->column("date_added")
            ->type("int")
            ->nullable();

        $proto->column("last_update_date")
            ->type("int")
            ->nullable();

        $proto->executeQuery();
        return $proto;
    }
    public function alter(string $table,callable $structure){
        $proto = new Structure($table);
        $proto->action = "altering";

        $structure($proto);
        $proto->executeQuery();
        return $proto;
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