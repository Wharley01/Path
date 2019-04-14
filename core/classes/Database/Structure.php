<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/5/2018
 * @Time 10:26 PM
 * @Project Path
 */

namespace Path\Core\Database;


use Path\Core\Database\Connections\MySql;
use Path\Core\Error\Exceptions;

class Structure
{
    public $table;
    /*
     *
     * @public $columns[]
     * @type array
     * [
     *      [
     *           "name" => (String) name of the column
     *           "new_name" => (String) name of the column
     *           "type" => (String) column type
     *           "is_nullable" => (Bool) Column can be null
     *           "default_value" => (Mixed) default value for the column
     *           "auto_increment" => (Bool) Column is auto incremented
     *           "is_dropping" => (Bool) whether to delete column or not
     *           "is_updating" => (Bool) whether to delete column or not
     *       ]
        ]
     * */
    private $columns = [];
    private $primary_keys = ["id"];
    private $unique_keys  = [];
    private $foreign_keys = [];
    private $foreign_key_references = [];
    public $action = "creating"; #(creating || altering)
    private $engine = "InnoDB";
    private $charset = "latin1";
    private $existing_columns = [];
    private $db_conn;

    public function __construct($table)
    {
        $this->db_conn = MySql::connection();
        $this->table = $table;
        $this->getExistingCols();
    }

    /**
     * @param $column
     * @return bool
     */
    private function colExists($column)
    {
        return in_array($column, $this->existing_columns);
    }

    private function getExistingCols()
    {
        try {
            $q = $this->db_conn->query("DESCRIBE {$this->table}");
            $cols = [];
            foreach ($q as $k) {
                $cols[] = $k["Field"];
            }
            $this->existing_columns = $cols;
        } catch (\PDOException $e) {
            $this->existing_columns = [];
        }
    }

    /**
     * @param $name
     * @return Structure
     */
    public function column($name)
    {
        $structure = [
            "name" => $name,
            "new_name" => $name
        ];

        array_push(
            $this->columns,
            $structure
        );
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function default($value)
    {
        $this->columns[count($this->columns) - 1]["default_value"] = $value;
        return $this;
    }

    public function dropColumn(...$columns)
    {
        if (!$columns) {
            $this->columns[count($this->columns) - 1]["is_dropping"] = true;
            echo "Dropping cols";
            return $this;
        }

        foreach ($columns as $column) {
            $this->column($column);
            $this->columns[count($this->columns) - 1]["is_dropping"] = true;
        }
        return $this;
    }

    public function update()
    {
        $this->columns[count($this->columns) - 1]["is_updating"] = true;
    }

    /**
     * @param $column
     * @return $this
     */
    public function rename($column)
    {
        if (isset($this->columns[count($this->columns) - 1]["name"])) {
            $this->columns[count($this->columns) - 1]["name"] = $column;
            $this->columns[count($this->columns) - 1]["new_name"] = $column;
        } else {
            $structure = [
                "name" => $column,
                "new_name" => $column
            ];

            array_push(
                $this->columns,
                $structure
            );
        }
        return $this;
    }

    public function to($new_column_name)
    {
        if (!isset($this->columns[count($this->columns) - 1]["name"]))
            throw new Exceptions\DataStructure("Specify column to rename");

        $this->columns[count($this->columns) - 1]["new_name"] = $new_column_name;
        $this->columns[count($this->columns) - 1]["is_updating"] = true;
        return $this;
    }

    public function type($type)
    {
        if (!isset($this->columns[count($this->columns) - 1]))
            throw new Exceptions\DataStructure("Specify a column to set type");

        $this->columns[count($this->columns) - 1]["type"] = $type;
        return $this;
    }

    public function nullable()
    {
        if (!isset($this->columns[count($this->columns) - 1]))
            throw new Exceptions\DataStructure("Specify a column ");

        $this->columns[count($this->columns) - 1]["is_nullable"] = true;
        return $this;
    }

    private function key($column = null, $type = "primary")
    {
        if (!$column) {
            if (!$this->columns[count($this->columns) - 1]["name"])
                throw new Exceptions\DataStructure("Column name not specified");
            array_push($this->{$type . "_keys"}, $this->columns[count($this->columns) - 1]["name"]);
            return $this;
        }

        $this->{$type . "_keys"} = array_merge($this->{$type . "_keys"}, $column);
        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function primaryKey(...$column)
    {
        $this->key($column, "primary");
        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function uniqueKey(...$column)
    {
        $this->key($column, "unique");
        return $this;
    }

    /**
     * @param $reference
     * @return $this
     */
    public function references($reference)
    {
        $this->key([], "foreign");
        $this->foreign_key_references[] = $reference;
        return $this;
    }

    public function increments()
    {
        if (!preg_match("/^int/mi", $this->columns[count($this->columns) - 1]["type"]))
            throw new Exceptions\DataStructure("You can only increment INT(integer) Column");

        $this->columns[count($this->columns) - 1]["auto_increment"] = true;
        return $this;
    }

    /**
     * @param $column_arr
     * @return string
     */
    private function genColQueryStr($column_arr)
    {
        $str = "";
        if (isset($column_arr["is_nullable"])) //check if it's nullable
            $str .= " NULL";
        else
            $str  .= " NOT NULL";

        if (isset($column_arr['default_value']))
            $str  .= " DEFAULT '{$column_arr['default_value']}'";

        if (isset($column_arr['auto_increment'])) //check if to auto increment column
            $str  .= " AUTO_INCREMENT";

        return $str;
    }
    public function getRawQuery()
    {

        if ($this->action == "creating") {
            $query = "
            CREATE TABLE IF NOT EXISTS `{$this->table}` (
            
            @appended_query
            
            ) ENGINE={$this->engine}  DEFAULT CHARSET={$this->charset}
            ";
        } else {
            $query = "
            ALTER TABLE `{$this->table}`  
            @appended_query  
            ";
        }
        $appended_query = "";

        if ($this->action == "creating") {
            foreach ($this->columns as $column) {
                $str = " `{$column['name']}` {$column['type']}";

                $str .= $this->genColQueryStr($column);

                if ($appended_query)
                    $appended_query .= ", " . $str;
                else
                    $appended_query  = $str;
            }
            $primary_key = join(",", array_unique($this->primary_keys));
            $appended_query .= ", PRIMARY KEY({$primary_key})";
            if ($this->unique_keys) {
                $unique_key = join(",", array_unique($this->unique_keys));
                $appended_query .= ", UNIQUE KEY({$unique_key})";
            }

            if ($this->foreign_keys) {
                //                $foreign_keys = join(",",array_unique($this->foreign_keys));
                for ($i = 0; $i < count($this->foreign_keys); $i++) {
                    $foreign_keys = $this->foreign_keys[$i];
                    $appended_query .= ", FOREIGN KEY({$foreign_keys}) REFERENCES {$this->foreign_key_references[$i]}";
                }
            }
        } elseif ($this->action == "altering") {
            foreach ($this->columns as $column) {
                $str  = "";
                if (!$this->colExists($column['name'])) {
                    $str .= " ADD ";
                    $str .= " `{$column['name']}` {$column['type']}";

                    $str .= $this->genColQueryStr($column);
                } else {
                    if (isset($column['is_dropping'])) {
                        $str .= " DROP ";
                        $str .= " `{$column['name']}`";
                    } else if (isset($column['is_updating'])) {
                        $str .= " CHANGE ";
                        $str .= " `{$column['name']}` `{$column['new_name']}` {$column['type']}";
                        $str .= $this->genColQueryStr($column);
                    }
                }

                if (strlen(trim($appended_query)) > 0) {
                    //                   echo "........TEST....'{$appended_query}'".PHP_EOL;
                    if (strlen(trim($str)) > 0) {
                        $appended_query .= ", " . $str;
                    }
                } else {
                    $appended_query  = $str;
                }
            }
        }

        $query = str_replace("@appended_query", $appended_query, $query);
        return $query;
    }

    public function executeQuery()
    {
        //        var_dump($this->columns);
        try {
            $query = $this->db_conn->query($this->getRawQuery());
        } catch (\PDOException $e) {
            throw new Exceptions\DataStructure($e->getMessage());
        }
        return $this->db_conn;
    }
}
