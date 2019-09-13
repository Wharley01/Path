<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/24/2018
 * @Time 12:16 PM
 * @Project Path
 */

namespace Path\Core\CLI\DefaultCommands;



use Path\Core\CLI\CInterface;

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
        ],
        "model" => [
            "desc" => "create database model, e.g: __path create model yourModelName"
        ],
        "email" => [
            "desc" => "create email Mailable, e.g: __path create email yourTemplateName"
        ]
    ];

    private $models_path = "path/Database/Model/";
    private $commands_path = "path/Commands/";
    private $route_controllers_path = "path/Controllers/Route/";
    private $live_controllers_path = "path/Controllers/Live/";
    private $migration_files_path = "path/Database/Migration";
    private $middleware_files_path = "path/Http/MiddleWares";
    private $email_templ_files_path = "path/Mail/Mailables";
    public function entry($params)
    {
        $params = (object)$params;
        if (isset($params->controller)) {
            $this->createController($params->controller);
        } elseif (isset($params->command)) {
            $this->createCommand($params->command);
        } elseif (isset($params->migration)) {
            $this->createMigration($params->migration);
        } elseif (isset($params->middleware)) {
            $this->createMiddleWare($params->middleware);
        } elseif (isset($params->model)) {
            $this->createModel($params->model);
        }elseif (isset($params->email)){
            $this->createMailable($params->email);
        }
    }

    private function toLower($string)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
    private function createCommand($command_file_name)
    {
        if (strlen($command_file_name) < 2)
            $command_file_name = $this->ask('Please Specify Command Name');
        $command_file_path = "{$this->commands_path}{$command_file_name}.php";
        if (file_exists($command_file_path)) {
            if ($this->confirm("Command already exist, do you want to overwrite? ")) {
                $command_file_open = fopen($command_file_path, "w");
                $this->writeCommandBoilerPlate($command_file_open, $command_file_name);
            }
        } else {
            $command_file_open = fopen($command_file_path, "w");
            $this->writeCommandBoilerPlate($command_file_open, $command_file_name);
        }
    }
    private function writeCommandBoilerPlate($write_instance, $command_file_name)
    {
        $command_name = $this->ask("Enter command name (if different from the file name): ") ?? strtolower($command_file_name);
        $command_desc = $this->ask("Enter a description for this command: ") ?? "";
        $command_param = $this->ask("Enter a parameter for this command: ");
        $command_param_desc = $this->ask("Enter the parameter's description: ");

        $code = "<?php


namespace Path\App\Commands;


use Path\Core\CLI\CInterface;

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
        if (!is_null($command_param)) {
            $code .= PHP_EOL . "
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
    public function entry(\$params)
    {
        var_dump(\$params);
    }

}
        ";
        fwrite($write_instance, $code);
        $this->write("[+] ----  Database model boiler plate for --`blue`{$command_file_name}`blue`-- generated in `green`\"{$this->commands_path}\"`green` " . PHP_EOL);
        fclose($write_instance);
    }
    private function createController($controller_name)
    {
        if ($controller_name === true) {
            $controller_name = $this->ask("Enter Controller's name", true);
        }
        //        Create file in Database/Models
        //        Create file controllers/

        if (!$this->confirm("Do you have existing Model for your controller? ")) {
            $_model_name = $this->ask("Enter new Model's Name:", true);
            $_db_model_file = "{$this->models_path}{$_model_name}.php";
            if (file_exists($_db_model_file)) {
                if ($this->confirm("{$_model_name} Database model Already exists, Override?")) {
                    $db_model_file = fopen($_db_model_file, "w");
                    $this->writeModelBoilerPlate($db_model_file, $_model_name);
                }
            } else {
                $db_model_file = fopen($_db_model_file, "w");
                $this->writeModelBoilerPlate($db_model_file, $_model_name);
            }
            $new_model_name = $_model_name;
        } else {
            $new_model_name = $this->ask("Enter Existing Model's Name", true);
        }

        if ($controller_type = strtolower($this->confirm('Controller Type', 'Route', 'Live'))) {
            $_controller_file = "{$this->route_controllers_path}{$controller_name}.php";
            if (file_exists($_controller_file)) {
                if ($this->confirm("{$controller_name} Controller Already exists, Override?", ['Yes', 'y'], ['No', 'n'])) {
                    $controller_file = fopen($_controller_file, "w");
                    $this->writeRouteControllerBoilerPlate($controller_file, $controller_name, $new_model_name);
                }
            } else {
                $controller_file = fopen($_controller_file, "w");
                $this->writeRouteControllerBoilerPlate($controller_file, $controller_name, $new_model_name);
            }
        } else {
            //            TODO: implement Live controller generation here
            $this->write('creating live controller');
            $_controller_file = "{$this->live_controllers_path}{$controller_name}.php";
            if (file_exists($_controller_file)) {
                if ($this->confirm("{$controller_name} Controller Already exists, Override?", ['Yes', 'y'], ['No', 'n'])) {
                    $controller_file = fopen($_controller_file, "w");
                    $this->writeLiveControllerBoilerPlate($controller_file, $controller_name, $new_model_name);
                }
            } else {
                $controller_file = fopen($_controller_file, "w");
                $this->writeLiveControllerBoilerPlate($controller_file, $controller_name, $new_model_name);
            }
        }
        echo PHP_EOL . PHP_EOL . "[+] ---- CLOSING -----" . PHP_EOL . PHP_EOL;

        echo $controller_name;
    }
    private function writeModelBoilerPlate($db_model_file, $model_name)
    {
        $model_boiler_plate = "<?php
/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\\App\\Database\Model;


use Path\Core\\Database\\Model;

class {$model_name} extends Model
{
    protected \$table_name               = \"" . $this->toLower($model_name) . "\";
    protected \$primary_key              = \"id\";
    public function __construct()
    {
        parent::__construct();
    }
}";
        //        Write model boiler plate code to file
        fwrite($db_model_file, $model_boiler_plate);
        echo PHP_EOL . PHP_EOL . "[+] ----  Database model boiler plate for --{$this->toLower($model_name)}-- generated" . PHP_EOL;
        fclose($db_model_file);
    }
    private function writeLiveControllerBoilerPlate($controller_file, $controller_name, $model_name)
    {
        $watchables = array_filter(explode(",", $this->ask("Enter Watchable methods, separate with comma if one than one")), function ($method) {
            return strlen(trim($method)) > 0;
        });


        $boiler_plate = "<?php
/*
* This Live Controller File Was automatically 
* Generated by Path
* Modify to Suite your needs,
* */

namespace Path\\App\\Controllers\\Live;

use Path\Core\\Storage\\Caches;
use Path\Core\\Http\\Response;
use Path\Core\\Http\\Watcher\\WatcherInterface;
use Path\Core\\Router\\Live\\Controller;
use Path\App\\Database\\Model\\$model_name;
use Path\Core\\Storage\\Sessions;


class $controller_name extends Controller
{
    ";
        if (count($watchables) > 0) {
            foreach ($watchables as $method) {
                $boiler_plate .= " 
    public \$$method;

     ";
            }
        }

        $boiler_plate .= "    
    //every time the watcher checks this Live Controller, it passes some data to it 
    public function __construct(
        WatcherInterface  &\$watcher,//watcher instance
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
        if (count($watchables) > 0) {
            foreach ($watchables as $method) {
                $boiler_plate .= " 
        \$this->$method =  \"dynamic value\";
        ";
            }
        }
        $boiler_plate .= "
    }
";
        if (count($watchables) > 0) {

            foreach ($watchables as $method) {
                $boiler_plate .= " 
public function $method(
        Response \$response,
        WatcherInterface  &\$watcher,
        Sessions \$sessions,
        ?String  \$message
    ){
//response here will be sent to client side when \$this->$method's value changes
        return \$response->json([\"from $method in $controller_name Live Controller\"]);
}
        ";
            }
        }

        $boiler_plate .= " 
    public function onMessage(
        WatcherInterface  &\$watcher,
        Sessions \$sessions,
        ?String  \$message
    ){

    }

    public function onConnect(
        WatcherInterface  &\$watcher,
        Sessions \$sessions,
        ?String  \$message
    ){

    }
               
}
        ";
        //        Write controller boiler plate
        fwrite($controller_file, $boiler_plate);
        $this->write(PHP_EOL."`light_green`Live Controller Boiler plate code generated in: `light_green` `light_blue`{$this->live_controllers_path}`light_blue`");
        fclose($controller_file);
    }
    private function writeRouteControllerBoilerPlate($controller_file, $controller_name, $model_name)
    {
        $contr_boiler_plate = "<?php

/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\App\\Controllers\\Route;


use Path\Core\\Router\\Route\\Controller;
use Path\Core\\Http\\Request;
use Path\Core\\Http\\Response;
use Path\Core\\Storage\\Sessions;

use Path\App\Database\Model\\$model_name;



class {$controller_name} extends Controller
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
        fwrite($controller_file, $contr_boiler_plate);
        echo PHP_EOL . PHP_EOL . "[+] ----  Controller boiler plate for --{$model_name}-- generated in \"{$this->route_controllers_path}\" " . PHP_EOL;
        fclose($controller_file);
    }
    private function createMigration($table_name)
    {
        $table_name = is_string($table_name) ? $table_name : $this->ask("Enter Table Name", true);
        $file_path = "{$this->migration_files_path}/{$table_name}.php";
        if (file_exists($file_path) && $this->confirm("Migration file already exists, do you want to override?")) { //check if file already
            //            open file
            $file_instance = fopen($file_path, "w");
            $this->writeMigrationBoilerPlate($file_instance, $table_name);
        } elseif (!file_exists($file_path)) {
            $file_instance = fopen($file_path, "w");
            $this->writeMigrationBoilerPlate($file_instance, $table_name);
        }
    }
    private function writeMigrationBoilerPlate($migration_file, $table_name)
    {
        $codes = "<?php
/*
* This FIle was automatically generated By Path
* Modify to your advantage
*/

namespace Path\App\\Database\\Migration;


use Path\Core\Database\Model;
use Path\Core\Database\Prototype;
use Path\Core\Database\Structure;
use Path\Core\Database\Table;



class {$table_name} implements Table
{
    public \$table_name = \"" . $this->toLower($table_name) . "\";
    public \$primary_key = \"id\";
    public function install(Structure &\$table)
    {


    }

    public function uninstall()
    {
    }

    public function populate(Model \$table)
    {

    }

    public function update(Structure &\$table)
    {

    }
}";
        fwrite($migration_file, $codes);
        $this->write("`green`[+]`green`  Migration Boiler Code Generated in {$this->migration_files_path} folder");
        fclose($migration_file);
    }
    private function createMiddleWare($middleware_name)
    {
        $middleware_name = is_string($middleware_name) ? $middleware_name : $this->ask("Enter MiddleWare Name", true);
        $file_path = "{$this->middleware_files_path}/{$middleware_name}.php";
        if (file_exists($file_path) && $this->confirm("MiddleWare file already exists, do you want to override?")) { //check if file already
            //            open file
            $file_instance = fopen($file_path, "w");
            $this->writeMiddleWarePlate($file_instance, $middleware_name);
        } elseif (!file_exists($file_path)) {
            $file_instance = fopen($file_path, "w");
            $this->writeMiddleWarePlate($file_instance, $middleware_name);
        }
    }
    private function writeMiddleWarePlate($middleware_file, $middleware_name)
    {
        $codes = "<?php
/*
* This file was automatically generated by Path
* Modify to suit your usage
* 
*/
namespace Path\App\Http\\MiddleWares;


use Path\Core\Http\\MiddleWare;
use Path\Core\Http\\Request;
use Path\Core\Http\\Response;

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

    public function response(Request \$request, Response \$response)
    {
            return \$response->json([\"msg\" => \"This json response is from \\\"{$middleware_name}\\\" MiddleWare, you get this response because \\\$this->validate() returns false\"]);
    }
}
";
        fwrite($middleware_file, $codes);
        $this->write("`green`[+]`green`  MiddleWare Boiler Code Generated in {$this->middleware_files_path} folder");
        fclose($middleware_file);
    }
    private function createModel($model_name)
    {

        $model_file = "{$this->models_path}{$model_name}.php";

        if (file_exists($model_file)) {
            if ($this->confirm("{$model_name} Database model Already exists, Override?")) {
                $db_model_file = fopen($model_file, "w");
                $this->writeModelBoilerPlate($db_model_file, $model_name);
            }
        } else {
            $db_model_file = fopen($model_file, "w");
            $this->writeModelBoilerPlate($db_model_file, $model_name);
        }
    }
    private function createMailable($email_name){
        $email_name = trim($email_name);

        $email_name = $email_name ?? $this->ask("Enter your email template name", true);

        $templ_file = $this->email_templ_files_path.'/'.$email_name.'.php';
        if (file_exists($templ_file)) {
            if ($this->confirm("{$email_name} Email template Already exists, Override?")) {
                $email_templ_file = fopen($templ_file, "w");
                $this->writeEmailBoilerCode($email_templ_file, $email_name);
            }
        } else {
            $email_templ_file = fopen($templ_file, "w");
            $this->writeEmailBoilerCode($email_templ_file, $email_name);
        }
    }

    /**
     * @param $email_templ_file
     * @param $email_name
     */
    private function writeEmailBoilerCode($email_templ_file, $email_name){

        $boiler_plate = "<?php

namespace Path\App\\Mail\\Mailables;

use Path\Core\\Database\\Model;
use Path\Core\\Mail\\Mailable;
use Path\Core\\Mail\\State;


class {$email_name} extends Mailable
{

    /*
    * Change this recipient details or set dynamically
    */
    public \$to = [
        \"email\" => \"recipient@provider.com\",
        \"name\"  => \"Recipient name\"
    ];

    /**
     * TestMail constructor.
     * @param State \$state
     */
    public function __construct(State \$state)
    {
    }

    public function title(State \$state):String
    {
        return \"this is the title\";
    }

    public function template(State \$state):String
    {
        return \"Hello {\$state->name}\";
    }

}";

        fwrite($email_templ_file, $boiler_plate);
        $this->write(PHP_EOL."`green`[+]`green` --  Email Template boiler plate for --`blue`{$email_name}`blue`-- generated in `green`\"{$this->email_templ_files_path}\"`green` " . PHP_EOL);
        fclose($email_templ_file);

    }
}
