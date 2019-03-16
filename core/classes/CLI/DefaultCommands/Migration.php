<?php


namespace Path\Console;


use Path\Console;
use Path\Database\Model;
use Path\Database\Prototype;
use Path\Database\Structure;
use Path\Database\Table;
use Path\DatabaseException;

import(
    "core/classes/Database/Prototype",
    "core/classes/Database/Structure",
    "core/classes/Database/Table",
    "core/classes/Database/Model"
);

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
        ]
    ];

    private $migration_files_path = "path/Database/Migration";
    private $migration_class_namespace = "Path\\Database\\Migration";
    private $tables = [];
    private $prototype;
    public function __construct()
    {
        $this->tables = $this->getAllMigrationClasses();
        $this->prototype = new Prototype();

    }

    /**
     * @param $params
     * @return mixed|void
     */
    public function entry($params)
    {
        $params = (array) $params;
        unset($params['app']);
        if(@$params['create-table'] && is_string(@$params['create-table'])){
            $this->createTable($params['create-table']);
        }else{
            unset($params['create-table']);
            foreach ($params as $param => $arg){
                $this->runCommand($param,$arg);
            }
        }

    }

    private function runCommand($hook,$table = null){
        if($table && is_string($table)){
            $table = strtolower($table);
            $table_class_instance =  $this->tables[$table];
            if(!$table_class_instance){
                throw new DatabaseException("\"{$table}\" Does not exist in {$this->migration_files_path}");
            }elseif($table_class_instance instanceof Table){
                $this->$hook($table,$table_class_instance);
            }else{
                throw new DatabaseException("{$table} Must implement Path\\Database\\Table");
            }
        }else{
            foreach ($this->tables as $table => $value){
                $table = strtolower($table);
                $table_class_instance =  $this->tables[$table];
                if(!$table_class_instance){
                    throw new DatabaseException("\"{$table}\" Does not exist in {$this->migration_files_path}");
                }elseif($table_class_instance instanceof Table){
                    $this->$hook($table,$table_class_instance);
                }else{
                    throw new DatabaseException("{$table} Must implement Path\\Database\\Table");
                }
            }
        }
    }

    private function install($table,$table_class_instance){
        $this->prototype->create($table,$table_class_instance);
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully installed");
    }
    private function uninstall($table){
        $this->prototype->drop($table);
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully uninstalled");
    }
    private function update($table,$table_class_instance){
        $this->prototype->alter($table,$table_class_instance);
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully Updated");
    }
    private function populate($table,Table $table_class_instance){
        $table_class_instance->populate(new class($table,$table_class_instance->primary_key ?? "id") extends Model{
            protected $table_name;
            protected $non_writable_cols        = [];
            protected $readable_cols            = [];
            protected $primary_key = "id";
            public function __construct($table,$primary_key)
            {
                $this->table_name = $table;
                $this->non_writable_cols[] = $primary_key;
                $this->primary_key = $primary_key;
                parent::__construct();
            }
        });
        $this->write("`green`[+]`green` `light_green`{$table}`light_green` Successfully Populated");

    }
    private function getAllMigrationClasses(){
        $classes = [];

        if ($handle = opendir($this->migration_files_path)) {
            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {
                    $class_name = basename($entry,".php");
                    import("{$this->migration_files_path}/{$class_name}");
                    $class_instance = $this->migration_class_namespace."\\".$class_name;
                    $class_instance = new $class_instance();
                    if(property_exists($class_instance,"table_name")){
                        $classes[$class_instance->table_name] = $class_instance;
                    }else{
                        $classes[strtolower($class_name)] = $class_instance;
                    }
                }
            }
            closedir($handle);
        }
        return $classes;
    }


}
        