<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/24/2018
 * @Time 12:16 PM
 * @Project Path
 */

namespace Path\Console;


//use Path\Console;

class Create extends CInterface
{
    public $name = "create";
    public $description = "start development server";
    public $arguments = [
        "controller" => [
            "desc" => "Accepts Controller name as argument"
        ],
        "command" => [
            "desc" => "Accepts Custom Command name as argument, e.g: __path create command customCommand"
        ],
        "migration" => [
            "desc" => "create migration file, e.g: __path create migration tableName"
        ],
        "middleware" => [
            "desc" => "create route middleware, e.g: __path create middleware yourMiddleWareName"
        ]
    ];

    private $models_path = "path/Database/Models/";
    private $commands_path = "path/Commands/";
    private $route_controllers_path = "path/Controllers/Route/";
    private $live_controllers_path = "path/Controllers/Live/";
    private $migration_files_path = "path/Database/Migration";
    private $middleware_files_path = "path/Http/MiddleWares";
    public function entry($params)
    {
        $params = (object)$params;
        if(isset($params->controller)){
            $this->createModel($params->controller);
        }elseif (isset($params->command)){
            $this->createCommand($params->command);
        }elseif (isset($params->migration)){
            $this->createMigration($params->migration);
        }elseif (isset($params->middleware)){
            $this->createMiddleWare($params->middleware);
        }

    }
    private function createCommand($command_file_name){
        if(strlen($command_file_name) < 2)
            $command_file_name = $this->ask('Please Specify Command Name');
        $command_file_path = "{$this->commands_path}{$command_file_name}.php";
        if(file_exists($command_file_path)){
            if($this->confirm("Command already exist, do you want to overwrite? ")){
                $command_file_open = fopen($command_file_path,"w");
                $this->writeCommandBoilerPlate($command_file_open,$command_file_name);
            }
        }else{
            $command_file_open = fopen($command_file_path,"w");
            $this->writeCommandBoilerPlate($command_file_open,$command_file_name);
        }
    }
    private function writeCommandBoilerPlate($write_instance, $command_file_name){
        $command_name = $this->ask("Enter command name (if different from the file name): ") ?? strtolower($command_file_name);
        $command_desc = $this->ask("Enter a description for this command: ") ?? "";
        $command_param = $this->ask("Enter a parameter for this command: ");
        $command_param_desc = $this->ask("Enter the parameter's description: ");

        $code = "<?php


namespace Path\Console;


use Path\Console;

class $command_file_name extends CInterface
{


    /*
     * Command Line name
     *
     * @var String
     * */
    public \$name = \"{$command_name}\";
    public \$description = \"{$command_desc}\";
    ";
        if(!is_null($command_param)){
            $code .= PHP_EOL."
    public \$arguments = [
        \"{$command_param}\" => [
            \"desc\" => \"{$command_param_desc}\"
        ]
    ];
            
            ";
        }

        $code .= "

    public function __construct()
    {
    }

    /**
     * @param \$params
     */
    public function entry(object \$params)
    {
        var_dump(\$params);
    }

}
        ";
        fwrite($write_instance,$code);
        echo PHP_EOL.PHP_EOL."[+] ----  Database model boiler plate for --{$command_file_name}-- generated in \"{$this->commands_path}\" ".PHP_EOL;
        fclose($write_instance);


    }
    private function createModel($controller_name){
        if($controller_name === true){
            $controller_name = $this->ask("Enter Controller's name",true);
        }
//        Create file in Database/Models
//        Create file controllers/

        if(!$this->confirm("Do you have existing Model for your controller? ")){
            $_model_name = $this->ask("Enter new Model's Name:",true);
            $_db_model_file = "{$this->models_path}{$_model_name}.php";
            if(file_exists($_db_model_file)){
                if($this->confirm("{$_model_name} Database model Already exists, Override?")){
                    $db_model_file = fopen($_db_model_file,"w");
                    $this->writeModelBoilerPlate($db_model_file,$_model_name);
                }
            }else{
                $db_model_file = fopen($_db_model_file,"w");
                $this->writeModelBoilerPlate($db_model_file,$_model_name);
            }
            $new_model_name = $_model_name;
        }else{
            $new_model_name = $this->ask("Enter Existing Model's Name",true);
        }

        if($controller_type = strtolower($this->confirm('Controller Type','Route','Live'))){
            $_controller_file = "{$this->route_controllers_path}{$controller_name}.php";
            if(file_exists($_controller_file)){
                if($this->confirm("{$controller_name} Controller Already exists, Override?",['Yes','y'],['No','n'])){
                    $controller_file = fopen($_controller_file,"w");
                    $this->writeRouteControllerBoilerPlate($controller_file,$controller_name,$new_model_name);
                }
            }else{
                $controller_file = fopen($_controller_file,"w");
                $this->writeRouteControllerBoilerPlate($controller_file,$controller_name,$new_model_name);
            }
        }else{
//            TODO: implement Live controller generation here
            $this->write('creating live controller');
            $_controller_file = "{$this->live_controllers_path}{$controller_name}.php";
            if(file_exists($_controller_file)){
                if($this->confirm("{$controller_name} Controller Already exists, Override?",['Yes','y'],['No','n'])){
                    $controller_file = fopen($_controller_file,"w");
                    $this->writeLiveControllerBoilerPlate($controller_file,$controller_name,$new_model_name);
                }
            }else{
                $controller_file = fopen($_controller_file,"w");
                $this->writeLiveControllerBoilerPlate($controller_file,$controller_name,$new_model_name);
            }
        }
        echo PHP_EOL.PHP_EOL."[+] ---- CLOSING -----".PHP_EOL.PHP_EOL;

        echo $controller_name;
    }
    private function writeModelBoilerPlate($db_model_file, $model_name){
        $model_boiler_plate = "<?php
/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Database\Models;


use Path\Database;

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
    private function writeLiveControllerBoilerPlate($controller_file, $controller_name, $model_name){
        $watchables = array_filter(explode(",",$this->ask("Enter Watchable methods, separate with comma if one than one")),function ($method){
            return strlen(trim($method)) > 0;
        });

        $boiler_plate = "<?php
/*
* This Live Controller File Was automatically 
* Generated by Path
* Modify to Suite your needs,
* */

namespace Path\\Controller\\Live;

use Path\\Storage\\Caches;
use Path\\Http\\Response;
use Path\\Http\\Watcher;
use Path\\LiveController;
use Path\Database\Models\\$model_name;
use Path\Storage\Sessions;

import(
    \"core/classes/Database/Model\",
    \"Path/Database/Models/Admin\"
);

class $controller_name implements LiveController
{
    ";
        if(count($watchables) > 0){
            foreach ($watchables as $method){
                $boiler_plate .=" 
    public \$$method;

     ";
            }
        }

        $boiler_plate .= "    
    //every time the watcher checks this Live Controller, it passes some data to it 
    public function __construct(
        Watcher  &\$watcher,//watcher instance
        Sessions \$sessions,//the session instance that can be used for auth. with the client side
        \$message//message sent from User(client Side)
    )
    {
      
        /*
        *  
        * you should set the value of each properties(which represents methods in this class)
        * to something that changes based on the return value
        * of the method they represents
        *
         */
";
        if(count($watchables) > 0){
            foreach ($watchables as $method){
                $boiler_plate .=" 
        \$this->$method =  \"dynamic value\";
        ";
            }
        }
        $boiler_plate .="
    }
";
        if(count($watchables) > 0){

            foreach ($watchables as $method){
                $boiler_plate .=" 
public function $method(
        Response \$response,
        Watcher  &\$watcher,
        Sessions \$sessions,
        ?String  \$message
    ){
//response here will be sent to client side when \$this->watch_list[\"$method\"]'s value changes
        return \$response->json([\"from $method in $controller_name Live Controller\"]);
}
        ";
            }

        }

        $boiler_plate .= "        

}
        ";
        //        Write controller boiler plate
        fwrite($controller_file,$boiler_plate);
        echo PHP_EOL.PHP_EOL."[+] ----  Controller boiler plate for --{$model_name}-- generated in \"{$this->live_controllers_path}\" ".PHP_EOL;
        fclose($controller_file);
    }
    private function writeRouteControllerBoilerPlate($controller_file, $controller_name, $model_name){
        $contr_boiler_plate = "<?php

/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Controller\Route;


use Path\Controller;
use Path\Http\Request;
use Path\Http\Response;
use Path\Database\Models\\$model_name;
use Path\Storage\Sessions;

import(\"Path/Database/Models/{$model_name}\");

class {$controller_name} implements Controller
{
    private \$session;
    public function __construct()
    {
        \$this->session = new Sessions();
    }
    public function fetchAll(Request \$request,Response \$response){
//     return a response here
        return \$response->json(['this is response from {$controller_name} Controller']);
    }

}";



//        Write controller boiler plate
        fwrite($controller_file,$contr_boiler_plate);
        echo PHP_EOL.PHP_EOL."[+] ----  Controller boiler plate for --{$model_name}-- generated in \"{$this->route_controllers_path}\" ".PHP_EOL;
        fclose($controller_file);
    }
    private function createMigration($table_name){
        $table_name = is_string($table_name) ? $table_name:$this->ask("Enter Table Name",true);
        $file_path = "{$this->migration_files_path}/{$table_name}.php";
        if(file_exists($file_path) && $this->confirm("Migration file already exists, do you want to override?")){//check if file already
//            open file
            $file_instance = fopen($file_path,"w");
            $this->writeMigrationBoilerPlate($file_instance,$table_name);

        }elseif (!file_exists($file_path)){
            $file_instance = fopen($file_path,"w");
            $this->writeMigrationBoilerPlate($file_instance,$table_name);
        }
    }
    private function writeMigrationBoilerPlate($migration_file,$table_name){
        $codes = "<?php
/*
* This FIle was automatically generated By Path
* Modify to your advantage
*/

namespace Path\Database\Migration;


use Path\Database\Model;
use Path\Database\Prototype;
use Path\Database\Structure;
use Path\Database\Table;

import(
    \"core/classes/Database/Prototype\",
    \"core/classes/Database/Table\",
    \"core/classes/Database/Structure\"
);

class {$table_name} implements Table
{
    public \$table_name = \"".strtolower($table_name)."\";
    public \$primary_key = \"id\";
    public function install(Structure &\$table)
    {
        \$table->column(\"name\")
            ->type(\"TEXT\")
            ->nullable();

    }

    public function uninstall()
    {
    }

    public function populate(Model \$table)
    {
        \$table->insert([
            \"name\" => \"I am testing\"
        ]);
    }

    public function update(Structure &\$table)
    {
        \$table->rename(\"name\")
            ->to(\"new_col_name\")
            ->type(\"TEXT\")
            ->nullable();
    }
}";
        fwrite($migration_file,$codes);
        $this->write("`green`[+]`green`  Migration Boiler Code Generated in {$this->migration_files_path} folder");
        fclose($migration_file);
    }
    private function createMiddleWare($middleware_name){
        $middleware_name = is_string($middleware_name) ? $middleware_name:$this->ask("Enter MiddleWare Name",true);
        $file_path = "{$this->middleware_files_path}/{$middleware_name}.php";
        if(file_exists($file_path) && $this->confirm("MiddleWare file already exists, do you want to override?")){//check if file already
//            open file
            $file_instance = fopen($file_path,"w");
            $this->writeMiddleWarePlate($file_instance,$middleware_name);

        }elseif (!file_exists($file_path)){
            $file_instance = fopen($file_path,"w");
            $this->writeMiddleWarePlate($file_instance,$middleware_name);
        }
    }
    private function writeMiddleWarePlate($middleware_file, $middleware_name){
        $codes = "<?php
/*
* This file was automatically generated by Path
* Modify to suit your usage
* 
*/
namespace Path\Http\\MiddleWare;


use Path\Http\\MiddleWare;
use Path\Http\\Request;
use Path\Http\\Response;

class {$middleware_name} implements MiddleWare
{

    /**
     * @param Request \$request
     * @param Response \$response
     * @return mixed
     * @throws \Path\\ConfigException
     * @internal param \$params
     */
    public function validate(Request \$request, Response \$response):bool
    {
        return false;
    }

    public function fallBack(Request \$request, Response \$response)
    {
            return \$response->json([\"msg\" => \"This json response is from \\\"{$middleware_name}\\\" MiddleWare, you get this response because \\\$this->validate() returns false\"]);
    }
}
";
        fwrite($middleware_file,$codes);
        $this->write("`green`[+]`green`  MiddleWare Boiler Code Generated in {$this->middleware_files_path} folder");
        fclose($middleware_file);
    }

}
