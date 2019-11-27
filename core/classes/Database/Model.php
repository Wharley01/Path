<?php


namespace Path\Core\Database;


use Path\Core\Database\Connections\MySql;
use Path\Core\Error\Exceptions;
use Path\Core\Misc\Validator;

abstract class Model
{
    private $conn;
    protected $table_name;
    private $model_name;
    protected $primary_key     = "id";
    protected $updated_col     = "last_update_date";
    protected $created_col     = "date_added";
    protected $record_per_page = 10;
    public $query_structure = [
        "WITH"              => "",
        "SELECT"            => [
            "query" => "",
            "columns" => []
        ],
        "AS"                => "",
        "JOIN"              => [], //table to join
        "ON"                => "", //associative array holding join condition
        "INSERT"            => "",
        "UPDATE"            => "",
        "WHERE"             => [
            "query" => "",
            "columns" => []
        ],
        "GROUP_BY"          => "",
        "HAVING"            => [
            "query" => "",
            "columns" => []
        ],
        "ORDER_BY"          => "",
        "LIMIT"             => ""
    ];

    public $params       = [
        "SELECT"            => [],
        "WHERE"             => [],
        "HAVING"            => [],
        "UPDATE"            => [],
        "INSERT"            => [],
        "SORT"              => [],
        "LIMIT"             => []
    ];

    protected $writable_cols        = []; //writable columns(Can be overridden)
    protected $non_writable_cols    = []; //non writable (Can be overridden)

    protected $readable_cols        = []; //readable columns(Can be overridden)
    protected $non_readable_cols    = []; //non readable (Can be overridden)

    private $writing              = []; //currently updated column and value
    private $reading              = []; //currently updated column and value
    public $last_insert_id;
    private $table_columns;
    public $columns;

    protected $fetch_method         = "FETCH_ASSOC";
    private $pages                = [];
    private $current_page        = 0;
    public $total_record         = 0;

    private $validator;
    private $valid_where_clause_rule = "^([\w\->\[\]\\d.]+)\s*([><=!]+)\\s*([\\w\->\[\]\\d]+)$";
    private $valid_column_rule = "^[_\w\.|\s\(\)\`\\'\",->\[\]!]+$";
    public $total_pages = 0;
    public $keys;

    public function __construct()
    {
        $this->conn = MySql::connection();
        $this->table_columns = $this->getColumns($this->table_name);
        $this->columns = $this->filterNonReadable($this->table_columns);
        $this->model_name =  get_class($this);
        $this->readable_cols = $this->convertColumnsToFull($this->readable_cols);
        $this->non_readable_cols = $this->convertColumnsToFull($this->non_readable_cols);
        $this->writable_cols = $this->convertColumnsToFull($this->writable_cols);
        $this->non_writable_cols = $this->convertColumnsToFull($this->non_writable_cols);
        $this->primary_key = $this->toFullName($this->primary_key);
        $this->updated_col = $this->toFullName($this->updated_col);
        $this->created_col = $this->toFullName($this->created_col);
        $this->generatekeys($this->table_name);
    }
    private function toFullName($column)
    {
        if (!$column || strlen(trim($column)) < 1)
            return null;

        if (strpos($column, ".") === false) {
            return $this->table_name . "." . $column;
        } else {
            return $column;
        }
    }
    private function convertColumnsToFull($columns)
    {
        $return = [];
        foreach ($columns as $column) {
            if (!$column || strlen(trim($column)) < 1)
                continue;
            if (strpos($column, ".") === false) {
                $return[] = $this->table_name . "." . $column;
            } else {
                $return[] = $column;
            }
        }
        return $return;
    }
    private function getColumns($table)
    {
        try {
            $q = $this->conn->query("DESCRIBE {$table}");
            $cols = [];
            foreach ($q as $k) {
                $cols[] = $table . "." . $k["Field"];
            }
            return $cols;
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }
    }

    private function generatekeys($table)
    {
        try {
            $q = $this->conn->query("DESCRIBE {$table}");
            $keys = [];
            foreach ($q as $k) {
                $keys[] = $k["Field"];
            }
            $this->keys = $keys;
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }
    }



    /**
     * @param $col
     * @return boolean
     */
    private function is_valid_col($col): bool
    {
        return preg_match("/$this->valid_column_rule/", $col);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @throws Exceptions\Database
     */
    public function set($key, $value)
    {
        $class_name = get_class($this);
        if ($this->isWritable($key)) {
            $this->writing[$key] = $value;
        } else {
            throw new Exceptions\Database("can't write {$key} in {$class_name}");
        }
        return $this;
    }


    public function raw_select_query()
    {
        $query      = $this->buildWriteRawQuery("SELECT");
        return (object)[
            "query"     => $query,
            "params"    => [
                "select" => $this->params["SELECT"],
                "where" => $this->params["WHERE"],
                "having" => $this->params["HAVING"],
                "limit" => $this->params["LIMIT"]
            ]
        ];
    }
    /**
     * @param $table
     * @return $this
     */
    public function table(String $table)
    {
        $this->table_name = $table;
        return $this;
    }
    private function where_gen(
        $conditions,
        $logic_gate = "AND",
        $type = "WHERE"
    ) {
        if (is_array($conditions)) {

            $str   = "";
            foreach ($conditions as $condition => $value) {
                if ($this->isJsonRef($condition)) {
                    $sql_exp = $this->getSqlJsonExp($condition);
                    $str .= "({$sql_exp}) = ? {$logic_gate}";
                    $this->params[$type][] = $value;
                    array_push($this->query_structure[$type]["columns"][], $sql_exp);
                } else {
                    $str .= " {$condition} = ? {$logic_gate} ";
                    $this->params[$type][] = $value;
                    array_push($this->query_structure[$type]["columns"], $condition);
                }
            }
            //            Remove trailing "AND"
            $str = preg_replace("/($logic_gate)\s*$/", "", $str);
            if (!$this->query_structure[$type]["query"]) {
                //                If no WHERE Clause already specified, add new one
                $this->query_structure[$type]["query"] = $str;
            } else {
                //                if there is already a WHERE clause, join with AND
                $this->query_structure[$type]["query"] .= " {$logic_gate} " . $str;
            }
        } else if (preg_match("/{$this->valid_where_clause_rule}/", $conditions)) {
            $str   = "";
            //          if conditions are in raw string
            $split = explode(",", $conditions);
            foreach ($split as $val) {
                preg_match("/{$this->valid_where_clause_rule}/", $val, $matches);
                $column = $matches[1];
                $exp_value = $matches[3];
                $equality_exp = $matches[2];
                //                check if column is referencing a json column
                if ($this->isJsonRef($column)) {
                    $sql_exp = $this->getSqlJsonExp($column);
                    $str .= "({$sql_exp}) {$equality_exp} ? {$logic_gate} ";
                    $this->params[$type][] = $exp_value;
                    array_push($this->query_structure[$type]["columns"], "({$sql_exp})");
                } else {
                    $str .= "{$column} {$equality_exp} ? {$logic_gate} ";
                    $this->params[$type][] = $exp_value;
                    array_push($this->query_structure[$type]["columns"], $column);
                }
            }
            //            Remove trailing "AND"
            $str = preg_replace("/(" . $logic_gate . ")\s*$/", "", $str);
            if (!$this->query_structure[$type]["query"]) {
                $this->query_structure[$type]["query"] = $str;
            } else {
                $this->query_structure[$type]["query"] .= " {$logic_gate} " . $str;
            }
        } elseif (preg_match("/$this->valid_column_rule/", $conditions)) {
            if ($this->isJsonRef($conditions)) {
                $conditions = $this->getSqlJsonExp($conditions);
            }
            if (!$this->query_structure[$type]["query"]) {
                $this->query_structure[$type]["query"] = $conditions;
            } else {
                $this->query_structure[$type]["query"] .= " {$logic_gate} " . $conditions;
            }
        } else {
            throw new Exceptions\Database("Invalid WHERE condition");
        }
    }

    /**
     * @param $key
     * @return bool
     */
    private function isWritable($key)
    {
        if ((in_array(trim($key), $this->writable_cols) || !in_array(trim($key), $this->non_writable_cols)) && in_array(trim($key), $this->table_columns)) {
            return true;
        }
        return false;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function writable(array $columns)
    {
        $this->writable_cols = array_merge($this->writable_cols, $this->convertColumnsToFull($columns));
        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function nonWritable(array $columns)
    {
        $this->non_writable_cols = array_merge($this->non_writable_cols, $this->convertColumnsToFull($columns));
        return $this;
    }
    /**
     * @param array $columns
     * @return $this
     */
    public function readable(array $columns)
    {
        $this->readable_cols = array_merge($this->readable_cols, $this->convertColumnsToFull($columns));
        return $this;
    }
    /**
     * @param array $columns
     * @return $this
     */
    public function nonReadable(array $columns)
    {
        $this->non_readable_cols = array_merge($this->non_readable_cols, $this->convertColumnsToFull($columns));
        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    private function isReadable($key)
    {
        if (in_array(trim($key), $this->readable_cols) || !in_array(trim($key), $this->non_readable_cols)) {
            return true;
        }
        return false;
    }
    private function filterNonWritable(array $data)
    {
        foreach ($data as $key => $value) {
            if (!$this->isWritable($this->table_name . '.' . $key)) {
                unset($data[$key]);
            }
            if ($this->isJsonRef($key)) {
                unset($data[$key]);
            }
        }
        return $data;
    }
    private function filterNonReadable(array $data)
    {
        foreach ($data as $index => $key) {
            if (!$this->isReadable($key)) {
                unset($data[$index]);
            }
        }
        return $data;
    }

    private function generateSqlObjFromArray($array, $root = "", $type = "UPDATE", $tree = [])
    {

        foreach ($array as $key => $value) {

            if (!is_array($value)) {
                $tree[] = "'$key'";
                $tree[] = "?";
                $this->params[$type][] = $value;
            } else {
                $tree[] = "'$key'";
                $tree[] = $this->generateSqlObjFromArray($array[$key], $key, $type, []);
            }
            unset($array[$key]);
        }
        $return = "JSON_OBJECT(" . join(",", $tree) . ")";
        //        echo "JSON_OBJECT(".join(",",$tree).")";
        return $return;
    }

    /**
     * @param array $data
     * @param string $type
     * @param string $json_action
     */

    private function rawKeyValueBind(array $data, $type = "UPDATE", $json_action = "UPDATE")
    {


        foreach ($data as $column => $value) {
            $string = "";
            if ($this->isJsonRef($column)) {
                $_value = is_array($value) ? $this->generateSqlObjFromArray($value) : "?";
                $func = $json_action == "UPDATE" ? "JSON_SET" : "JSON_INSERT";
                $_column = $this->genJsonPath($column);
                if (@$this->query_structure[$type]) {
                    $this->query_structure[$type] .= ",{$_column['column']} = {$func}({$_column['column']},'{$_column['path']}',$_value)";
                    if ($_value == "?") {
                        $this->params[$type][] = $value;
                    }
                } else {
                    $this->query_structure[$type] .= "{$_column['column']} = {$func}({$_column['column']},'{$_column['path']}',$_value) ";
                    if ($_value == "?") {
                        $this->params[$type][] = $value;
                    }
                }
                $string = preg_replace("/,\s*$/", "", $string); //remove trailing comma
            } else {
                $value = is_array($value) ? json_encode($value) : $value;
                $string = "";
                if (@$this->query_structure[$type]) {
                    $this->query_structure[$type] .= ",{$column} = ?";
                    $this->params[$type][] = $value;
                } else {
                    $this->query_structure[$type] .= "{$column} = ?";
                    $this->params[$type][] = $value;
                }
                $string = preg_replace("/,\s*$/", "", $string); //remove trailing comma
            }
        }
    }
    /**
     * @param $conditions
     * @return $this
     */
    public function where(
        $conditions
    ) {
        $this->where_gen($conditions, "AND");
        return $this;
    }

    public function whereCreatedSince($days)
    {
        $where = "from_unixtime({$this->created_col}) >= date_sub(now(), interval {$days} day)";
        if ($this->query_structure["WHERE"]["query"]) {
            $this->query_structure["WHERE"]["query"] .= " AND " . $where;
        } else {
            $this->query_structure["WHERE"]["query"] = $where;
        }
        return $this;
    }

    public function whereUpdatedSince($days)
    {
        $where = "from_unixtime({$this->updated_col}) >= date_sub(now(), interval {$days} day)";
        if ($this->query_structure["WHERE"]["query"]) {
            $this->query_structure["WHERE"]["query"] .= " AND " . $where;
        } else {
            $this->query_structure["WHERE"]["query"] = $where;
        }
        return $this;
    }

    public function whereNotUpdatedSince($days)
    {
        $where = "from_unixtime({$this->updated_col}) < date_sub(now(), interval {$days} day)";
        if ($this->query_structure["WHERE"]["query"]) {
            $this->query_structure["WHERE"]["query"] .= " AND " . $where;
        } else {
            $this->query_structure["WHERE"]["query"] = $where;
        }
        return $this;
    }

    //    public function whereJsonIncludes($column, $needle){
    //     if($this->query_structure["WHERE"]){
    //         $this->query_structure["WHERE"] = "AND JSON_CONTAINS($needle,$column,'{$_condition['path']}') > 0 {$logic_gate}";
    //         var_dump($this->genJsonPath($condition));
    //     }
    //
    //    }

    public function rawWhere(
        $where,
        ...$params
    ) {
        if (strlen(trim($this->query_structure["WHERE"]["query"])) > 0) {
            $this->query_structure["WHERE"]["query"] .= " AND " . $where;
        } else {
            $this->query_structure["WHERE"]["query"] = $where;
        }
        $this->params["WHERE"] = array_merge($this->params["WHERE"], $params);
        return $this;
    }

    public function whereColIsNull($col){
        $this->rawWhere("{$col} IS NULL");
        return $this;
    }

    public function whereColIsNotNull($col){
        $this->rawWhere("{$col} IS NOT NULL");
        return $this;
    }

    public function identify(
        $id
    ) {
        if (!$this->primary_key)
            throw new Exceptions\Database("specify primary key in {$this->model_name}");


        $this->where_gen([$this->primary_key => $id], "AND");
        return $this;
    }

    public function orWhere(
        $conditions,
        $params = null
    ) {
        $this->where_gen($conditions, "OR");
        return $this;
    }

    private function genRawJsonSelect($col)
    {
        $column = $this->genJsonPath($col);

        if ($this->query_structure["SELECT"]["query"]) {
            $this->query_structure["SELECT"]["query"] .= ", " . $column['column'] . "->>\"" . $column['path'] . "\" as '{$column['column']}->{$column['path']}'";
            $this->query_structure["SELECT"]["columns"][] = $column['column'] . "->>\"" . $column['path'] . "\" as '{$column['column']}->{$column['path']}'";
        } else {
            $this->query_structure["SELECT"]["query"] = $column['column'] . "->>\"" . $column['path'] . "\"  as '{$column['column']}->{$column['path']}'";
            $this->query_structure["SELECT"]["columns"][] = $column['column'] . "->>\"" . $column['path'] . "\"  as '{$column['column']}->{$column['path']}'";
        }
    }

    private function getSqlJsonExp($col)
    {
        $column = $this->genJsonPath($col);
        $return = $column['column'] . "->>\"" . $column['path'] . "\"";
        return $return;
    }

    private function rawColumnGen($cols)
    {
        foreach ($cols as $col) {
            if ($col instanceof Model) {
                $this->generateRawSelectFromInstance($col);
            } else {
                if ($this->isJsonRef($col)) {
                    $this->genRawJsonSelect($col);
                } else {
                    if ($this->query_structure["SELECT"]["query"]) {
                        $this->query_structure["SELECT"]["query"] .= "," . $col;
                    } else {
                        $this->query_structure["SELECT"]["query"] = $col;
                    }
                }
            }
        }
    }
    private function buildWriteRawQuery($command = "UPDATE")
    {
        switch ($command) {
            case "UPDATE":
                $columns     = $this->query_structure[$command];
                $command    = "UPDATE " . $this->table_name . " SET ";
                $query      = $command . $columns;
                if ($this->query_structure["WHERE"]["query"])
                    $query .= " WHERE " . $this->query_structure["WHERE"]["query"];
                break;
            case "INSERT":
                $columns     = $this->query_structure[$command];
                $command    = "INSERT INTO {$this->table_name} SET {$columns}";
                $query      = $command;
                break;
            case "DELETE":
                $query      = "DELETE FROM {$this->table_name} ";

                if ($this->query_structure["WHERE"]["query"])
                    $query .= PHP_EOL . " WHERE " . $this->query_structure["WHERE"]["query"];
                break;
            case "SELECT":
                $columns     = $this->query_structure["SELECT"]["query"];
                $query      = "SELECT SQL_CALC_FOUND_ROWS {$columns}";
                $query     .= PHP_EOL . " FROM {$this->table_name} ";
                if ($this->query_structure["JOIN"]) {
                    $query .= " " . $this->rawJoinGen($this->query_structure["JOIN"]);
                }
                if (@$this->query_structure["WHERE"]["query"])
                    $query .= PHP_EOL . " WHERE " . $this->query_structure["WHERE"]["query"];
                if (@$this->query_structure["GROUP_BY"])
                    $query .= PHP_EOL . " GROUP BY " . $this->query_structure["GROUP_BY"];
                if (@$this->query_structure["HAVING"]["query"])
                    $query .= PHP_EOL . " HAVING " . $this->query_structure["HAVING"]["query"];
                if (@$this->query_structure['ORDER_BY'])
                    $query .= PHP_EOL . " ORDER BY " . $this->query_structure['ORDER_BY'];
                if (@$this->query_structure['LIMIT'])
                    $query .= PHP_EOL . " LIMIT " . $this->query_structure['LIMIT'];
                break;
            case "SORT":
                $columns     = $this->query_structure["SELECT"]["query"];
                $query      = "SELECT {$columns} FROM {$this->table_name} ";
                if ($this->query_structure["WHERE"]["query"])
                    $query .= " WHERE " . $this->query_structure["WHERE"]["query"];
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
        //        generate available pages
        $total_records = $this->total_record;
        $current_page = 0;
        $page = $this->current_page;
        $total_pages = ceil($total_records / $this->record_per_page);

        while ($current_page  < $total_pages) {
            $this->pages[] = [
                "page_number"   => $current_page + 1,
                "navigable"     => ($current_page != $page),
                "total_pages"   => $total_pages
            ];
            $current_page++;
        }
        return $this->pages;
    }

    private function compileData($data)
    { }

    /**
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $validator->model = $this;
        $this->validator = $validator;
        return $this;
    }

    private function rawUpdate()
    {

        $data[$this->updated_col] = time();

        //        Process and execute query

        $query      = $this->buildWriteRawQuery("UPDATE");
        $params     = array_merge($this->params["UPDATE"], $this->params["WHERE"]);
        //        var_dump($params);
        //        echo PHP_EOL.$query;
        try {
            $prepare    = $this->conn->prepare($query); //Prepare query\
            $prepare->execute($params);
            $this->clearMemory();
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }

        return $this;
    }

    public function update(array $data = [])
    {
        if (!$data)
            $data = $this->filterNonWritable($this->writing);
        elseif ($data and is_array($data))
            $data = array_merge($this->filterNonWritable($data), $this->writing);

        //        if(!$data)
        //            throw new Exceptions\Database("Error Attempting to update Empty data set");
        if (!$this->table_name)
            throw new Exceptions\Database("No Database table name specified, Configure Your model or  ");

        $data[$this->updated_col] = time();
        if ($this->validator instanceof Validator) {
            if ($this->validator->hasError()) {
                throw new Exceptions\Database("Validation failed");
            }
        }


        //        GET and set raw query from array
        $this->rawKeyValueBind($data, "UPDATE");
        //        Process and execute query

        $query      = $this->buildWriteRawQuery("UPDATE");
        $params     = array_merge($this->params["UPDATE"], $this->params["WHERE"]);
        //        var_dump($params);
        //        echo PHP_EOL.$query;
        try {
            $prepare    = $this->conn->prepare($query); //Prepare query\
            $prepare->execute($params);
            $this->clearMemory();
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }

        return $this;
    }

    public function increment($column)
    {
        if ($this->query_structure["UPDATE"]) {
            $this->query_structure["UPDATE"] = ", {$column} = {$column} + 1";
        } else {
            $this->query_structure["UPDATE"] = "{$column} = {$column} + 1";
        }
        return $this;
    }

    public function decrement($column)
    {
        if ($this->query_structure["UPDATE"]) {
            $this->query_structure["UPDATE"] = ", {$column} = {$column} - 1";
        } else {
            $this->query_structure["UPDATE"] = "{$column} = {$column} - 1";
        }
        return $this;
    }

    public function insertJson($data)
    {

        if (!$data)
            throw new Exceptions\Database("Error Attempting to update Empty data set");

        $data[$this->updated_col] = time();
        if ($this->validator instanceof Validator) {
            if ($this->validator->hasError()) {
                return false;
            }
        }


        //        GET and set raw query from array
        $this->rawKeyValueBind($data, "UPDATE", "INSERT");
        //        Process and execute query

        $query      = $this->buildWriteRawQuery("UPDATE");
        $params     = array_merge($this->params["UPDATE"], $this->params["WHERE"]);
        //        var_dump($params);
        //        echo PHP_EOL.$query;
        try {
            $prepare    = $this->conn->prepare($query); //Prepare query\
            $prepare->execute($params);
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }

        return $this;
    }

    public function insert(array $data = null)
    {
        if (!$data)
            $data = $this->writing;
        elseif ($data and is_array($data))
            $data = array_merge($this->filterNonWritable($data), $this->writing);

        if (!$data)
            throw new Exceptions\Database("Error Attempting to update Empty data set");
        if (!$this->table_name)
            throw new Exceptions\Database("No Database table name specified, Configure Your model or  ");
        //        add miscellinouse data
        $data[$this->updated_col] = time();
        $data[$this->created_col ?? "date_added"] = time();
        if ($this->validator instanceof Validator) {
            if ($this->validator->hasError()) {
                return false;
            }
        }

        //        GET and set raw query from array
        $this->rawKeyValueBind($data, "INSERT");
        //        Process and execute query

        $query      = $this->buildWriteRawQuery("INSERT");
        $params     = $this->params["INSERT"];
        //        var_dump($params);
        //        echo PHP_EOL.$query;

        try {
            $prepare    = $this->conn->prepare($query); //Prepare query\
            $prepare->execute($params);
            $this->last_insert_id = $this->conn->lastInsertId();
            $this->clearMemory();
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }
        return $this;
    }
    public function delete()
    {
        if (!$this->table_name)
            throw new Exceptions\Database("No Database table name specified, Configure Your model or  ");

        $query = $this->buildWriteRawQuery("DELETE");
        $params = $this->params["WHERE"];
        //        var_dump($params);
        //        echo PHP_EOL.$query;
        try {
            $prepare    = $this->conn->prepare($query); //Prepare query\
            $prepare->execute($params);
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }
        $this->clearMemory();
        return $this;
    }

    /**
     * @param array $cols
     * @param bool $sing_record
     * @return array|mixed
     * @throws Exceptions\Database
     */
    public function all(
        $cols = [],
        $sing_record = false
    ) {

        if (is_array($cols)) {
            if (!$cols) {
                if ((is_array($this->readable_cols) && count($this->readable_cols) > 0)) {
                    $cols = $this->filterNonReadable($this->readable_cols);
                } elseif (is_array($this->non_readable_cols) && count($this->non_readable_cols) > 0) {
                    $cols = $this->filterNonReadable($this->table_columns);
                } else {
                    $cols = $this->filterNonReadable($this->table_columns);
                }
            }

            $cols = $this->filterNonReadable($cols);

            //            if(!$cols)
            //                throw new Exceptions\Database("Error Attempting to update Empty data set");
            if (!$this->table_name)
                throw new Exceptions\Database("No Database table name specified, Configure Your model or  ");
            if (count($cols) > 0) {
                $this->rawColumnGen($cols);
            }
        }else{
            if (is_string($cols)){
                $cols = explode(",", $cols);
                $this->rawColumnGen($cols);
            }
        }
        $query      = $this->buildWriteRawQuery("SELECT");
        $params     = array_merge($this->params["SELECT"], $this->params["WHERE"], $this->params["HAVING"], $this->params["LIMIT"]);

        //        var_dump($params);
//                echo "<br>".$query."<br>";
        try {
            $prepare                = $this->conn->prepare($query); //Prepare query\
            $prepare->execute($params);
            $this->total_record     = $this->conn->query("SELECT FOUND_ROWS()")->fetchColumn();
            $this->total_pages = ceil($this->total_record / $this->record_per_page);

            if ($sing_record) {
                $this->clearMemory();
                return $prepare->fetch(constant("\PDO::{$this->fetch_method}"));
            } else {
                $this->clearMemory();
                return $prepare->fetchAll(constant("\PDO::{$this->fetch_method}"));
            }
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }
    }

    private function clearMemory()
    {

        $this->query_structure = [
            "WITH"              => "",
            "SELECT"            => [
                "query" => "",
                "columns" => []
            ],
            "AS"                => "",
            "JOIN"              => [], //table to join
            "ON"                => "", //associative array holding join condition
            "INSERT"            => "",
            "UPDATE"            => "",
            "WHERE"             => [
                "query" => "",
                "columns" => []
            ],
            "GROUP_BY"          => "",
            "HAVING"            => [
                "query" => "",
                "columns" => []
            ],
            "ORDER_BY"          => "",
            "LIMIT"             => ""
        ];

        $this->params       = [
            "SELECT"            => [],
            "WHERE"             => [],
            "HAVING"            => [],
            "UPDATE"            => [],
            "INSERT"            => [],
            "SORT"              => [],
            "LIMIT"             => []
        ];
    }

    public function getAll(
        $cols = [],
        $sing_record = false
    ) {
        return $this->all($cols, $sing_record);
    }


    /**
     * @param bool $sing_record
     * @return array|mixed
     * @throws Exceptions\Database
     * @internal param array $cols
     */
    public function get(
        $sing_record = false
    ) {
        return $this->all([], $sing_record);
    }
    /**
     * @param int $_from
     * @param int $_to
     * @return $this
     */
    public function batch($_from = 0, $_to = 10)
    {
        $this->query_structure["LIMIT"] = "?,?";
        $this->params["LIMIT"]          = [$_from, $_to];
        return $this;
    }

    public function getPage($page = 1,$cols = [])
    {
        //        get total record
        $page -= 1;
        $this->current_page = $page;
        $offset =  $this->record_per_page * $page;
        $total = $this->record_per_page;

        $this->query_structure["LIMIT"] = "?,?";
        $this->params["LIMIT"]          = [$offset, $total];
        return $this->all();
    }

    public function sortBy($sort)
    {
        if (is_array($sort)) {
            $str = "";
            foreach ($sort as $key => $val) {
                $str .= " {$key} {$val},";
            }
            $str = preg_replace("/,$/", "", $str);
            if ($this->query_structure["ORDER_BY"]) {
                $this->query_structure["ORDER_BY"] .= ", " . $str;
            } else {
                $this->query_structure["ORDER_BY"] = $str;
            }
        } else {
            if ($this->query_structure["ORDER_BY"]) {
                $this->query_structure["ORDER_BY"] .= ", " . $sort;
            } else {
                $this->query_structure["ORDER_BY"] = $sort;
            }
        }
        return $this;
    }
    public function like($wild_card)
    {
        if (!$this->query_structure["WHERE"]["query"])
            throw new Exceptions\Database("WHERE Clause is empty");

        $this->query_structure["WHERE"]["query"] .= " LIKE ?";
        $this->params["WHERE"][] = "$wild_card";
        return $this;
    }
    public function notLike($wild_card)
    {
        if (!$this->query_structure["WHERE"]["query"])
            throw new Exceptions\Database("WHERE Clause is empty");

        $this->query_structure["WHERE"]["query"] .= " NOT LIKE ?";
        $this->params["WHERE"][] = "$wild_card";
        return $this;
    }
    public function between($start, $stop)
    {
        if (!$this->query_structure["WHERE"]["query"])
            throw new Exceptions\Database("WHERE Clause is empty");

        $this->query_structure["WHERE"]["query"] .= " BETWEEN ? AND ?";
        $this->params[] = $start;
        $this->params[] = $stop;
        return $this;
    }

    private function getOn($arr)
    {
        foreach ($arr as $key => $value) {
            return $key . " = " . $value;
        }
        return "";
    }


    public function rawJoinGen(array $table_joins)
    {
        $str = "";
        foreach ($table_joins as $table => $value) {
            $type = $value["type"];
            $on   = $this->getOn($value["on"]);
            $str .= PHP_EOL . " " . $type . " " . $table . " " . PHP_EOL . "   " . "ON" . " " . $on;
        }
        return $str;
    }
    /**
     * @param string $type
     * @param $table
     * @param $on
     * @return $this
     */
    private function join($type = "INNER", $table, $on)
    {
        $this->query_structure["JOIN"][$table]["type"] =  $type . " JOIN";
        $this->query_structure["JOIN"][$table]["on"] =  $on;
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function leftJoin($table, $on)
    {
        $this->join("LEFT", $table, $on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function innerJoin($table, $on)
    {
        if ($table instanceof Model) {
            $table = $table->table_name;
        }

        $this->join("INNER", $table, $on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function rightJoin($table, $on)
    {
        $this->join("RIGHT", $table, $on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param $table
     * @param $on
     * @return $this
     */
    public function fullJoin($table, $on)
    {
        $this->join("FULL", $table, $on);
        /** @var Model $this */
        return $this;
    }

    /**
     * @param array $cols
     * @return object
     */
    public function first($cols = []): object
    {
        $this->query_structure["ORDER_BY"] = "{$this->primary_key} ASC";
        $this->query_structure["LIMIT"]    = "0,1";
        return (object)$this->all($cols, true);
    }

    /**
     * @param $cols
     * @return object
     */
    public function getFirst($cols = [])
    {
        return $this->first($cols);
    }

    /**
     * @param array $cols
     * @return object
     */
    public function last($cols = []): object
    {
        $this->query_structure["ORDER_BY"] = "{$this->primary_key} DESC";
        $this->query_structure["LIMIT"]    = "0,1";
        return (object)$this->all($cols, true);
    }


    /**
     * @return object
     */
    public function getLast()
    {
        return $this->last();
    }

    /**
     * @return mixed
     */
    public function count()
    {
        $this->query_structure["SELECT"]["query"] = "COUNT(*)";
        return (int) $this->all(null, true)["COUNT(*)"];
    }

    /**
     * @param $col
     * @return $this
     */
    public function max($col)
    {
        $this->query_structure["ORDER_BY"] = "{$col} DESC";
        $this->query_structure["LIMIT"]    = "0,1";
        return $this;
    }

    /**
     * @param $col
     * @return $this
     */
    public function min($col)
    {
        $this->query_structure["ORDER_BY"] = "{$col} ASC";
        $this->query_structure["LIMIT"]    = "0,1";
        return $this;
    }

    private function isJsonRef($selector)
    {
        return strpos($selector, "->") !== false;
    }
    private function genJsonPath($selector)
    {
        //            typical selector looks this way column->jsonProp->another
        $selector = explode("->", $selector);
        $column = $selector[0];
        $path = "$";
        for ($i = 1; $i < count($selector); $i++) {
            $curr_key = trim($selector[$i]);
            if ($curr_key[0] == "[" && $curr_key[strlen($curr_key) - 1] == "]") {
                $path .= $selector[$i];
            } else {
                $path .= "." . $selector[$i];
            }
        }
        return [
            "column" => $column,
            "path" => $path
        ];
    }

    private function generateRawSelectFromInstance(Model $select)
    {
        $raw = $select->raw_select_query();
        if ($this->query_structure["SELECT"]["query"]) {
            $this->query_structure["SELECT"]["query"] .= ", (" . str_replace("SQL_CALC_FOUND_ROWS", "", $raw->query) . ")";
            $this->params["SELECT"] = array_merge($this->params["SELECT"], $raw->params["select"]);
            $this->params["WHERE"] = array_merge($this->params["WHERE"], $raw->params["where"]);
            $this->params["HAVING"] = array_merge($this->params["HAVING"], $raw->params["having"]);
            $this->params["LIMIT"] = array_merge($this->params["LIMIT"], $raw->params["limit"]);
        } else {
            $this->query_structure["SELECT"]["query"] = " (" . str_replace("SQL_CALC_FOUND_ROWS", "", $raw->query) . ")";
            $this->params["SELECT"] = array_merge($this->params["SELECT"], $raw->params["select"]);
            $this->params["WHERE"] = array_merge($this->params["WHERE"], $raw->params["where"]);
            $this->params["HAVING"] = array_merge($this->params["HAVING"], $raw->params["having"]);
            $this->params["LIMIT"] = array_merge($this->params["LIMIT"], $raw->params["limit"]);
        }
    }

    /**
     * @param array ...$columns
     * @return $this
     * @throws Exceptions\Database
     */
    public function select(...$columns)
    {
        if (count($columns) == 1 && $columns[0] instanceof Model) {
            $columns = $columns[0];
            $this->generateRawSelectFromInstance($columns);
        } else {
            if (is_array($columns)) {

                if (!$this->table_name)
                    throw new Exceptions\Database("No Database table name specified, Configure Your model or  ");
                $columns = $this->filterNonReadable($columns);
                if (!$columns)
                    throw new Exceptions\Database("Can't read empty sets of columns in \"select()\" Method ");

                $this->rawColumnGen($columns);
            } else {
                if ($this->isJsonRef($columns)) {
                    $this->genRawJsonSelect($columns);
                } else {
                    if ($this->query_structure["SELECT"]["query"]) {
                        $this->query_structure["SELECT"]["query"] .= ", " . $columns;
                    } else {
                        $this->query_structure["SELECT"]["query"] = $columns;
                    }
                }
            }
        }

        return $this;
    }

    public function rawSelect($expression, ...$params)
    {
        if ($this->query_structure["SELECT"]["query"]) {
            $this->query_structure["SELECT"]["query"] .= ", " . $expression;
        } else {
            $this->query_structure["SELECT"]["query"] = $expression;
        }
        $this->query_structure["SELECT"]["columns"][] = $expression;
        $this->params["SELECT"] = array_merge($this->params["SELECT"], $params);
        return $this;
    }
    /**
     * @param $alias
     * @return $this
     */
    public function as($alias)
    {
        if ($this->query_structure["SELECT"]["query"]) {
            $this->query_structure["SELECT"]["query"] .= " AS " . $alias . " ";
        }
        return $this;
    }
    public function groupBy($col)
    {
        if (is_array($col)) {
            $this->query_structure["GROUP_BY"] = join(",", $col);
        } else {
            if (!$this->is_valid_col($col))
                throw new Exceptions\Database("Invalid Column name \"{$col}\" in \"groupBy()\" method ");

            $this->query_structure["GROUP_BY"] = $col;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        if (strlen(trim($this->query_structure["SELECT"]["query"])) > 0) {
            $this->query_structure["SELECT"]["query"] .= ", COUNT({$this->primary_key}) as total";
        } else {
            $this->query_structure["SELECT"]["query"] .= " COUNT({$this->primary_key}) as total";
        }
        return $this->all(null, true)["total"] > 0;
    }
    /**
     * @return bool
     */
    public function doesntExists(): bool
    {
        if (strlen(trim($this->query_structure["SELECT"]["query"])) > 0) {
            $this->query_structure["SELECT"]["query"] .= ", COUNT({$this->primary_key}) as total";
        } else {
            $this->query_structure["SELECT"]["query"] .= " COUNT({$this->primary_key}) as total";
        }
        return $this->all(null, true)["total"] < 1;
    }

    /**
     * @param array ...$cols
     * @return $this
     */
    public function whereColumns(...$cols)
    {
        if ($this->query_structure["WHERE"]["query"]) {
            $this->query_structure["WHERE"]["query"] .= " AND " . "MATCH( " . join(",", $cols) . " )";
        } else {
            $this->query_structure["WHERE"]["query"] = " MATCH( " . join(",", $cols) . " )";
        }
        array_merge($this->query_structure["WHERE"]["columns"], $cols);
        return $this;
    }

    /**
     * @param array ...$cols
     * @return $this
     */
    public function orWhereColumns(...$cols)
    {
        if ($this->query_structure["WHERE"]["query"]) {
            $this->query_structure["WHERE"]["query"] .= " OR " . "MATCH( " . join(",", $cols) . " )";
        } else {
            $this->query_structure["WHERE"]["query"] = " MATCH( " . join(",", $cols) . " )";
        }
        array_merge($this->query_structure["WHERE"]["columns"], $cols);
        return $this;
    }

    public function in(array $list)
    {
        $array = join(",", array_pad(array(), count($list), "?"));
        $this->query_structure["WHERE"]["query"] .= " IN ({$array})";
        $this->params["WHERE"] = array_merge($this->params["WHERE"], $list);
        return $this;
    }

    /**
     * @param $value
     * @param string $mode
     * @return  $this
     */
    public function matches($value, $mode = "BOOLEAN")
    {
        $this->query_structure["WHERE"]["query"] .= " AGAINST(? IN {$mode} MODE)";
        $this->params["WHERE"][]         = $value;
        return $this;
    }

    public function having($condition)
    {
        $this->where_gen($condition, "AND", "HAVING");
        return $this;
    }

    /**
     * @param int $record_per_page
     * @return $this
     */
    public function setRecordPerPage(int $record_per_page)
    {
        $this->record_per_page = $record_per_page;
        return $this;
    }

    public function getKeys(){
        return $this->keys;
    }
}
