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
    public $indexes = [];
    public $action = "creating"; #(creating || altering)
    private $engine = "InnoDB";
    private $charset = "utf8mb4";
    private $collation = "utf8mb4_unicode_ci";
    private $existing_columns = [];
    private $db_conn;

    public function __construct($table)
    {
        $this->db_conn = MySql::connection();
        $this->table = $table;
        $this->charset = config('DATABASE->charset') ?? $this->charset;
        $this->collation = config('DATABASE->collation') ?? $this->collation;
        $this->engine = config('DATABASE->engine') ?? $this->engine;
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
            $q = $this->db_conn->query("DESCRIBE `{$this->table}`");
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
     * @param $expression
     * @param bool $stored
     * @return $this
     */
    public function as($expression, $stored = true)
    {
        $this->columns[count($this->columns) - 1]["expression"] = $expression;
        $this->columns[count($this->columns) - 1]["store_expression"] = $stored;
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
            return $this;
        }

        foreach ($columns as $column) {
            $this->column($column);
            $this->columns[count($this->columns) - 1]["is_dropping"] = true;
        }
        return $this;
    }

    public function withConstraint($constraint){
        $this->columns[count($this->columns) - 1]["constraint"] = $constraint;
        return $this;
    }

    public function placeAfter($column){
        $this->columns[count($this->columns) - 1]["position"] = " AFTER {$column}";
        return $this;
    }

    public function placeFirst($column){
        $this->columns[count($this->columns) - 1]["position"] = " FIRST";
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

    private function key(
        $column,
        $type = "primary key",
        $reference = null,
        $delete_constraint = null,
        $update_constraint = null,
        $new_column = null
    )
    {

        if (!$column) {
            if (!$this->columns[count($this->columns) - 1]["name"])
                throw new Exceptions\DataStructure("Column name not specified");

            $this->indexes[strtoupper($type)][] = [
                'column' => $this->columns[count($this->columns) - 1]["name"],
                'new_column' => $this->columns[count($this->columns) - 1]["new_name"],
                'references' => $reference,
                'del_constraint' => $delete_constraint,
                'update_constraint' => $update_constraint
            ];

            return $this;
        }

        $this->indexes[strtoupper($type)][] = [
            'column' => $column,
            'new_column' => $new_column ?? $column,
            'references' => $reference,
            'del_constraint' => $delete_constraint,
            'update_constraint' => $update_constraint
        ];

        return $this;
    }

    /**
     * @param $column
     * @return $this
     * @throws Exceptions\DataStructure
     */
    public function primaryKey(...$column)
    {
        $this->key($column, "primary key");
        return $this;
    }

    public function indexColumn(...$column){
        $this->key($column, "index");
        return $this;
    }

    /**
     * @param $column
     * @return $this
     * @throws Exceptions\DataStructure
     */
    public function uniqueKey(...$column)
    {
        $this->key($column, "unique");
        return $this;
    }

    public function ordinaryIndex(...$column){
        $this->key($column, "index");
        return $this;
    }

    public function fullText(...$column){
        $this->key($column, " fulltext");
        return $this;
    }

    /**
     * @param $reference
     * @param string $delete_constraint
     * @param string $update_constraint
     * @return $this
     * @throws Exceptions\DataStructure
     */
    public function references($reference, $delete_constraint = null, $update_constraint = null)
    {
        $column = $this->columns[count($this->columns) - 1]["name"];
        $this->key("`$column`", "foreign key",$reference,$delete_constraint,$update_constraint);
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
     * @param bool $creating
     * @return string
     */
    private function genColQueryStr($column_arr,$creating = false)
    {
        $str = "";
        if (isset($column_arr["is_nullable"])) //check if it's nullable
            $str .= " NULL";
        else
            $str  .= " NOT NULL";

        if (array_key_exists('default_value',$column_arr)){
            if($column_arr['default_value'] === "CURRENT_TIMESTAMP"){
                $default = "DEFAULT CURRENT_TIMESTAMP";
            }else{
                $default = "DEFAULT '{$column_arr['default_value']}'";
            }
            $str  .= " ".$default;
        }

        if (isset($column_arr['auto_increment'])) //check if to auto increment column
            $str  .= " AUTO_INCREMENT";

        if(array_key_exists('position',$column_arr) && !$creating){
            $str .= $column_arr['position'];
        }

        return $str;
    }

    private function generateIndex(&$appended_query,$creating = true){
        foreach ($this->indexes as $index => $column_arr){
//                loop through the columns

            foreach ($column_arr as $_column){
//                var_dump($_column);
                $column_name = $_column['column'];
                $new_column_name = $_column['new_column'] ?? null;
                $references = $_column['references'];
                $delete_constraint = $_column['del_constraint'] ?? null;
                if($delete_constraint){
                    $delete_constraint = "ON DELETE {$delete_constraint}";
                }else{
                    $delete_constraint = "";
                }
                $update_constraint = $_column['update_constraint'] ?? null;
                if($update_constraint){
                    $update_constraint = "ON UPDATE {$update_constraint}";
                }else{
                    $update_constraint = "";
                }
                $add = $creating ? '':'ADD ';
//                var_dump($new_column_name);
                $k = $new_column_name ?? $column_name;
                $appended_query .= ",$add ".strtoupper($index)."({$k})";

                if($references){
                    $appended_query .= " REFERENCES {$references} {$delete_constraint} {$update_constraint}";
                }
            }
        }
    }
    public function getRawQuery()
    {

        if ($this->action == "creating") {
            $query = "
            CREATE TABLE IF NOT EXISTS `{$this->table}` (
            
            @appended_query
            
            ) ENGINE={$this->engine}  DEFAULT CHARSET={$this->charset} COLLATE {$this->collation};
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
                $col_type = $column['type'] ?? null;
                $col_name = $column['name'] ?? null;
                $expression = $column['expression'] ?? null;
                $expression_store = $column['store_expression'] ?? null;
                if(!$col_type)
                    throw new Exceptions\Database('specify column type of '.$col_name);
                $str = " `{$col_name}` {$col_type} ";
                if($expression)
                    $str .= "GENERATED ALWAYS AS (".$expression.") ".($expression_store ? ' STORED':'');
                else
                    $str .= $this->genColQueryStr($column,true);

                if (strlen(trim($appended_query)) > 0)
                    $appended_query .= ", " . $str;
                else
                    $appended_query  .= $str;
            }
        } elseif ($this->action == "altering") {
            foreach ($this->columns as $column) {
//                var_dump($column);
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


                if (strlen(trim($appended_query)) > 0)
                    $appended_query .= ", " . $str;
                else
                    $appended_query  .= " " . $str;

            }

        }

        if(strlen(trim($appended_query)) < 1){
            return null;
        }

        $this->generateIndex($appended_query,$this->action == "creating");
//        var_dump($appended_query);
        $query = str_replace("@appended_query", $appended_query, $query);
        return $query;
    }

    public function executeQuery()
    {

        try {
            $raw_query = $this->getRawQuery();
//            echo $raw_query;
            if($raw_query)
                $query = $this->db_conn->query($raw_query);
        } catch (\PDOException $e) {
            throw new Exceptions\DataStructure($e->getMessage());
        }
        return $this->db_conn;
    }
}
