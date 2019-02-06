<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 12:03 AM
 * @Project Path
 */

namespace Path;

class Console
{
    protected   $args;
    protected   $accepted_cmds;
    protected   $commands = [];
    private     $cmd_entry = "entry";
    static      $foreground_colors = array(
        'bold'         => '1',    'dim'          => '2',
        'black'        => '0;30', 'dark_gray'    => '1;30',
        'blue'         => '0;34', 'light_blue'   => '1;34',
        'green'        => '0;32', 'light_green'  => '1;32',
        'cyan'         => '0;36', 'light_cyan'   => '1;36',
        'red'          => '0;31', 'light_red'    => '1;31',
        'purple'       => '0;35', 'light_purple' => '1;35',
        'brown'        => '0;33', 'yellow'       => '1;33',
        'light_gray'   => '0;37', 'white'        => '1;37',
        'normal'       => '0;39',
    );

    static      $background_colors = array(
        'black'        => '40',   'red'          => '41',
        'green'        => '42',   'yellow'       => '43',
        'blue'         => '44',   'magenta'      => '45',
        'cyan'         => '46',   'light_gray'   => '47',
    );

    static         $options = array(
        'underline'    => '4',    'blink'         => '5',
        'reverse'      => '7',    'hidden'        => '8',
    );

    public function __construct($args)
    {
        $this->args = $args;
    }

    public static function build(
        $text       = '',
        $color      = 'normal',
        $newline    = true,
        $bg         = null
    ){
        $write = $text;
        $write = "\033[".Console::$foreground_colors[$color]. "m".$write;
        if($newline)
            $write = $write.PHP_EOL;
        if(!is_null($bg))
            $write = "\033[".Console::$background_colors[$bg]. "m".$write;

        return $write. "\033[0m";
    }

    /**
     *
     */
    public function loadAllCommands(){
        if ($handle = opendir("Path/Commands")) {

            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {
                    $cli_class_name = basename($entry,".php");
//                    load_class("CLI/Commands/".$cli_class_name);
                    require_once "Path/Commands/{$cli_class_name}.php";
                    $class = "Path\Console\\".$cli_class_name;
                    try{
                        $class = new $class();
                        $this->commands[$class->name]['class'] = $class;
                        $this->commands[$class->name]['class_name'] = "Path\Console\\".$cli_class_name;
                        $this->commands[$class->name]['entry'] = $this->cmd_entry;
                        if(isset($class->arguments)){
                            $this->commands[$class->name]['arguments'] = $class->arguments;
                        }
                        $this->commands[$class->name]["description"] = @$class->description ?? "Description not Available";
                    }catch (\Throwable $e){
                        echo PHP_EOL.self::build("There is an error in:","light_red").PHP_EOL;
                        echo self::build($e->getMessage(),"red");
                        echo self::build($e->getTraceAsString(),"red");
                        continue;
                    }

                }
            }

            closedir($handle);
        }
    }

    public static function validateArgs($arguments){

    }

    /**
     * @param String $command
     * @param array $args
     * @return bool
     */
    public static function shouldRun(String $command, Array $args):bool {
        return count(get_cli_args([$command],$args)) > 0;
    }

    private function getAllCommands(){
        return array_values(array_keys($this->commands));
    }

    public function executeCLI(){
        $commands = array_values(array_filter(array_keys($this->commands),function ($cmd){
            return self::shouldRun($cmd,$this->args);
        }));//filter those commands not entered by user to console
        $commands_unfitered = array_values(array_filter(array_values($this->args),function ($cmd){
            return self::shouldRun($cmd,$this->args);
        }));//filter those commands not entered by user to console
        if(count($commands) > 0){
            $commands = [@$commands[0]];
//            get all commands
            foreach ($commands as $command){

//            The initiated class of the command
                $method = $this->commands[$command]['class'];
                $args = [];
                if(isset($this->commands[$command]['arguments'])){
                    $args = array_keys($this->commands[$command]['arguments']);
//               TODO: Validate argument
                }
                array_push($args,$command);
                try{
                    $method->{$this->commands[$command]['entry']}((object) get_cli_args($args,$this->args));
                }catch (\Throwable $e){
                    echo PHP_EOL.self::build("There was error in {$this->commands[$command]['class_name']}->{$this->commands[$command]['entry']}()","light_red").PHP_EOL;
                    echo PHP_EOL.self::build($e->getMessage(),"red").PHP_EOL.PHP_EOL;
                    echo self::build($e->getTraceAsString(),"red");
                }
                echo PHP_EOL;
            }
        }else{

            echo PHP_EOL.self::build("Invalid Commands: ".self::build(join(" , ",$commands_unfitered),"red",false).", write \"php path explain\" to seee list of available commands ","light_red",true);
        }

    }
    public function loadDefaultCLIs(){
        //        add some default commands
        $this->commands["explain"]['class']               = $this;
        $this->commands["explain"]['entry']               = "getCommandsDetails";
        $this->commands["explain"]["description"]         = "This command shows all details about a CLI";

    }
    public function getCommandsDetails($argument){
        echo PHP_EOL;
        if($argument->explain === true){
            foreach ($this->commands as $cmd => $desc){
                $mask1 = "%30.30s      %30s \n";
                printf($mask1,$this::build("".$cmd."    ",'light_green',false),$desc["description"]);
                if(isset($desc["arguments"])){
                $mask2 = "%32.30s    %30s \n";
                    //                Out put all supported arguments
                    foreach ($desc["arguments"] as $arg => $value){
                        printf($mask2,self::build($arg,'green',false),$value['desc']."                                                        ");
                    }
                }

                echo PHP_EOL.PHP_EOL;


            }
        }else{
            if(!isset($this->commands[$argument->explain])){
                echo self::build("!{$argument->explain} not a recognized Command ","red")."".self::build("You can create a custom CLI Command in Path/core/classes/CLI/Commands Folder","light_green");
            }else{
                $mask = "%-5s          %30.30s\n";
                printf($mask,$this::build($argument->explain,'light_green',false),$this->commands[$argument->explain]["description"]);

                $mask = "--- %-5s   %18s\n";
                foreach ($this->commands[$argument->explain]["arguments"] as $arg => $value){
                    printf($mask,self::build($arg,'green',false),$value['desc']);
                }
                echo PHP_EOL.PHP_EOL;
            }
        }
//        var_dump();
//        var_dump($argument);
    }
}