<?php


namespace Path\Core\CLI\DefaultCommands;


use Path\Core\CLI\CInterface;
use Path\Core\Database\Connections\MySql;
use Path\Core\Database\Model;
use Path\Core\Database\Prototype;
use Path\Core\Database\Table;
use Path\Core\Error;
use Path\Core\Error\Exceptions;
use Path\Core\Storage\Caches;


class Migration extends CInterface
{


    /*
     * Command Line name
     *
     * @var String
     * */
    public $name = "app";
    public $description = "App Migration";


    public $arguments = [
        "install" => [
            "desc" => "Install application database, you can specify table as parameter"
        ],
        "uninstall" => [
            "desc" => "uninstall application database(this drops all table and data n it, not reversible), you can specify table as parameter"
        ],
        "populate" => [
            "desc" => "Runs populate hook in your migration classes, you can specify table as parameter"
        ],
        "update" => [
            "desc" => "Runs Update hook in your migration classes"
        ],
        "activate" => [
            "desc" => "Activate model for Usage"
        ],
        "describe" => [
            "desc" => "Runs Update hook in your migration classes"
        ],
        "-force" => [
            "desc" => "force uninstall(Ignores foreign key checks)"
        ]
    ];

    private $migration_files_path = "path/Database/Migration";
    private $migration_class_namespace = "Path\App\\Database\\Migration";
    private $tables = [];
    private $prototype;
    private $conn;

    public function __construct()
    {
    }

    /**
     * @param $params
     * @return mixed|void
     */
    public $params;

    public function entry($params)
    {
        $this->tables = $this->getAllMigrationClasses();
        $this->prototype = new Prototype();
        $params = (array)$params;
        unset($params['app']); //remove default root param

        $this->params = $params;
        $this->conn = MySql::connection();

        foreach ($params as $param => $arg) {
            $this->runCommand($param, $arg);
        }

    }
    private function toLower($string)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    private function showLoading($interval,$length = 500){
        $this->write(PHP_EOL.'`green`[`green`');
        for ($i = 0; $i < $length; $i++){
            usleep($interval);
            $this->write('++');
        }
        $this->write('`green`]`green`'.PHP_EOL.PHP_EOL);
    }
    private function runCommand($hook, $table = null)
    {
        $this->showLoading(10 * 1000,40 );
        if(array_key_exists('-force',$this->params)){
            MySql::disableKeyCheck();
            $this->write(PHP_EOL."`red`[+]`red` disable key check".PHP_EOL);
        }
        if($hook == "-force")
            return;

        if ($table && is_string($table)) {
            $table = $this->toLower($table);
            $table_class_instance =  $this->tables[$table];
            if (!$table_class_instance) {
                throw new Exceptions\Database("\"{$table}\" Does not exist in {$this->migration_files_path}");
            } elseif ($table_class_instance instanceof Table) {
                $this->$hook($table, $table_class_instance);
                if($hook == "install" || $hook == "update"){
                    $this->activate($table, $table_class_instance);
                }
            } else {
                throw new Exceptions\Database("{$table} Must implement Path\\Database\\Table");
            }
        } else {
            foreach ($this->tables as $table => $value) {
                $table = $this->toLower($table);
                $table_class_instance =  $this->tables[$table];
                if (!$table_class_instance) {
                    throw new Exceptions\Database("\"{$table}\" Does not exist in {$this->migration_files_path}");
                } elseif ($table_class_instance instanceof Table) {
                    $this->$hook($table, $table_class_instance);
                    if($hook == "install" || $hook == "update"){
                        $this->activate($table, $table_class_instance);
                    }
                } else {
                    throw new Exceptions\Database("{$table} Must implement Path\\Database\\Table");
                }
            }
        }
    }

    private function install($table, $table_class_instance)
    {
        $this->prototype->create($table, $table_class_instance);
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully installed " . PHP_EOL);
    }

    private function activate($table,$table_class_instance){
        $cache_path = "table_{$table}_cols";
        $write_path = ROOT_PATH.'path/Database/Activation/';

        try {
            $q = $this->conn->query("DESCRIBE `{$table}`");
            $cols = [];
            foreach ($q as $k) {
                $cols[] = $table . "." . $k["Field"];
            }
            if($cols = json_encode($cols,JSON_PRETTY_PRINT)){
                Caches::set( $cache_path, $cols, $write_path);
                $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully Activated " . PHP_EOL);
            }else{
                throw new Exceptions\Database('Unable to generate migration column JSON');
            }
        } catch (\PDOException $e) {
            throw new Exceptions\Database($e->getMessage());
        }
    }

    private function uninstall($table)
    {

        $this->prototype->drop($table);
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully uninstalled " . PHP_EOL);
    }
    private function update($table, $table_class_instance)
    {
        $this->prototype->alter($table, $table_class_instance);
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully Updated " . PHP_EOL);
    }
    private function populate($table, Table $table_class_instance)
    {
        $table_class_instance->populate(new class ($table, $table_class_instance->primary_key ?? "id") extends Model
        {
            protected $table_name;
            protected $non_writable_cols        = [];
            protected $readable_cols            = [];
            protected $primary_key = "id";
            public function __construct($table, $primary_key)
            {
                $this->table_name = $table;
                $this->non_writable_cols[] = $primary_key;
                $this->primary_key = $primary_key;
                parent::__construct();
            }
        });
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully Populated");
    }
    private function printTableToTerminal($table)
    {
        try{
            $q = (MySql::connection())->query("DESCRIBE `{$table}`");
            $cols = [];

            $this->write(PHP_EOL . PHP_EOL . "{$table}" . PHP_EOL);

            $this->write("`white`______________________________________________________________________`white`" . PHP_EOL);
            $mask = "| %-30.30s | %-15.30s | %-15.30s |\n";
            printf($mask, "Column", "Type", "Default Value");
            printf($mask, "", "", "");
            $str = "";
            foreach ($q as $k) {
                printf($mask, $k["Field"], $k["Type"], $k["Default"] === NULL ? "NULL" : $k["Default"]);
            }
            $this->write("`white`______________________________________________________________________`white`");
            //        var_dump($cols);
        }catch (Exceptions\Database $exception){
            $this->write('`red`'.$exception->getMessage().'`red`');
        }

    }
    private function describe($table)
    {
        if (is_string($table)) {
            $this->printTableToTerminal($table);
        } else {
            foreach ($this->tables as $table) {
                var_dump($table);
            }
        }
    }

    private function getAllMigrationClasses()
    {
        $classes = [];

        if ($handle = opendir($this->migration_files_path)) {
            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {
                    $class_name = basename($entry, ".php");
                    $file_info = pathinfo($entry);
                    $file_path = "{$this->migration_files_path}/{$entry}";

                    if (
                        is_readable($file_path) &&
                        $file_info["extension"] == "php"
                    ) {
                        if (strpos($class_name, "\\") === false)
                            $class_instance = $this->migration_class_namespace . "\\" . $class_name;
                        else
                            $class_instance = $class_name;
                        $class_instance = new $class_instance();
                        if (property_exists($class_instance, "table_name")) {
                            $classes[$class_instance->table_name] = $class_instance;
                        } else {
                            $classes[$this->toLower($class_name)] = $class_instance;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $classes;
    }
}
