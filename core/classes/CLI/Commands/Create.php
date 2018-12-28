<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/24/2018
 * @Time 12:16 PM
 * @Project Path
 */

namespace Path\Console;


use Path\Console;

class Create extends CLInterface
{
    public $name = "create";
    public $description = "start development server";
    public $arguments = [
        "model" => [
            "desc" => "model name"
        ]
    ];

    public function entry(object $params)
    {
        if(isset($params->model)){
            $this->createModel($params->model);
        }

    }
    private function createModelBoilerPlate($db_model_file,$model_name){
        $model_boiler_plate = "<?php
/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Database\Models;


use Data\Model;

class {$model_name} extends Model
{
    protected \$table_name               = \"".strtolower($model_name)."\";
    protected \$non_writable_cols        = [\"id\"];
    protected \$readable_cols            = [\"id\",\"name\",\"description\"];

    public function __construct()
    {
        parent::__construct();
    }
}";
        //        Write model boiler plate code to file
        fwrite($db_model_file,$model_boiler_plate);
        echo PHP_EOL.PHP_EOL."[+] ----  Database model boiler plate for --{$model_name}-- generated".PHP_EOL;
        fclose($db_model_file);
    }
    private function createContrBoilerPlate($contr_file,$model_name){
        $contr_boiler_plate = "<?php

/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Controller;


use Path\Controller;
use Path\Http\Request;
use Path\Http\Response;
use Path\Database\Models;

load_class([
    \"Database/Models/{$model_name}\",
    \"Controller\"
]);
class {$model_name} implements Controller
{
    public  \$_".strtolower(trim($model_name)).";

    public function __construct()
    {
        \$this->_".strtolower(trim($model_name))." = (new Models\\".$model_name."());
    }
    public function fetchAll(Request \$request,Response \$response){
//     return a response here
    }

}";



//        Write controller boiler plate
        fwrite($contr_file,$contr_boiler_plate);
        echo PHP_EOL.PHP_EOL."[+] ----  Controller boiler plate for --{$model_name}-- generated".PHP_EOL;
        fclose($contr_file);
    }
    private function createModel($model_name){
//        Create file in Database/Models
        $_db_model_file = "core/classes/Database/Models/{$model_name}.php";
//        Create file controllers/
        $_contr_file = "core/controllers/{$model_name}.php";

        if(file_exists($_db_model_file)){
            if($this->confirm("{$model_name} Database model Already exists, Override?")){
                $db_model_file = fopen($_db_model_file,"w");
                $this->createModelBoilerPlate($db_model_file,$model_name);
            }
        }else{
            $db_model_file = fopen($_db_model_file,"w");
            $this->createModelBoilerPlate($db_model_file,$model_name);
        }

        if(file_exists($_contr_file)){
            if($this->confirm("{$model_name} Controller Already exists, Override?")){
                $contr_file = fopen($_contr_file,"w");
                $this->createContrBoilerPlate($contr_file,$model_name);
            }
        }else{
            $contr_file = fopen($_contr_file,"w");
            $this->createContrBoilerPlate($contr_file,$model_name);
        }

        echo PHP_EOL.PHP_EOL."[+] ---- CLOSING -----".PHP_EOL.PHP_EOL;

        echo $model_name;
    }
}
