<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 12:03 AM
 * @Project Path
 */

namespace Path\Core\CLI;

class Console extends CInterface
{
    protected   $args;
    protected $default_cmd_path = "core/classes/CLI/DefaultCommands/";
    protected $user_cmd_namespace = "Path\\App\\Commands\\";
    protected $default_cmd_namespace = "Path\\Core\\CLI\\DefaultCommands\\";
    protected $user_cmd_path = "path/Commands/";

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

    /**
     *
     */
    public function loadAllCommands()
    {
        /*
         * Load default commands
         * */
        if ($handle = opendir($this->default_cmd_path)) {
            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {
                    $cli_class_name = basename($entry, ".php");
                    //                    load_class("CLI/Commands/".$cli_class_name);
                    require "{$this->default_cmd_path}/{$cli_class_name}.php";
                    $class = $this->default_cmd_namespace.$cli_class_name;
                    try {
                        $class = new $class();
                        $this->commands[$class->name]['class'] = $class;
                        $this->commands[$class->name]['class_name'] = "Path\Console\\" . $cli_class_name;
                        $this->commands[$class->name]['entry'] = $this->cmd_entry;
                        if (isset($class->arguments)) {
                            $this->commands[$class->name]['arguments'] = $class->arguments;
                        }
                        $this->commands[$class->name]["description"] = @$class->description ?? "Description not Available";
                    } catch (\Throwable $e) {
                        $this->write("`light_red` There is an error in: `light_red`".PHP_EOL);
                        $this->write('`red`'.$e->getMessage().'`red` '.PHP_EOL);
                        $this->write('`red`'.$e->getTraceAsString().'`red`'.PHP_EOL);
                        continue;
                    }
                }
            }
            closedir($handle);
        }

        if ($handle = opendir($this->user_cmd_path)) {

            while (false !== ($entry = readdir($handle))) {

                if ($entry != "." && $entry != "..") {
                    $cli_class_name = basename($entry, ".php");
                    if (is_readable(ROOT_PATH . '/' . $this->user_cmd_path . $cli_class_name . '.php')) {

                        $class = $this->user_cmd_namespace.$cli_class_name;

                        try {
                            $class = new $class();
                            $this->commands[$class->name]['class'] = $class;
                            $this->commands[$class->name]['class_name'] = "Path\Console\\" . $cli_class_name;
                            $this->commands[$class->name]['entry'] = $this->cmd_entry;
                            if (isset($class->arguments)) {
                                $this->commands[$class->name]['arguments'] = $class->arguments;
                            }
                            $this->commands[$class->name]["description"] = @$class->description ?? "Description not Available";
                        } catch (\Throwable $e) {


                            $this->write("`light_red` There is an error in: `light_red` \n ");
                            $this->write('`red`'.$e->getMessage().'`red` '.PHP_EOL);
                            $this->write('`red`'.$e->getTraceAsString().'`red` '.PHP_EOL);
                            continue;
                        }
                    }
                }
            }

            closedir($handle);
        }
    }

    public static function validateArgs($arguments)
    { }

    /**
     * @param String $command
     * @param array $args
     * @return bool
     */
    public static function shouldRun(String $command, array $args): bool
    {
        return count(get_cli_args([$command], $args)) > 0;
    }

    private function getAllCommands()
    {
        return array_values(array_keys($this->commands));
    }

    public function executeCLI()
    {
        $commands = array_values(
            array_filter(
                array_keys($this->commands),function ($cmd) {
                    return self::shouldRun($cmd, $this->args);
                }
            )
        ); //filter those commands not entered by user to console
        $commands_unfitered = array_values(
            array_filter(
                array_values($this->args), function ($cmd) {
                    return self::shouldRun($cmd, $this->args);
                }
            )
        ); //filter those commands not entered by user to console
        if (count($commands) > 0) {
            $commands = [@$commands[0]];
            //            get all commands
            foreach ($commands as $command) {

                //            The initiated class of the command
                $method = $this->commands[$command]['class'];
                $args = [];
                if (isset($this->commands[$command]['arguments'])) {
                    $args = array_keys($this->commands[$command]['arguments']);
                    //               TODO: Validate argument
                }
                array_push($args, $command);
                try {
                    $method->{$this->commands[$command]['entry']}(get_cli_args($args, $this->args));
                } catch (\Throwable $e) {
                     $this->write(PHP_EOL."`light_red` There was error in {$this->commands[$command]['class_name']}->{$this->commands[$command]['entry']} `light_red` \n");
                    $this->write('`red`'.$e->getMessage().'`red` '.PHP_EOL);
                    $this->write('`red`'.$e->getTraceAsString().'`red` '.PHP_EOL);
                }
                echo PHP_EOL;
            }
        } else {

            $this->write(PHP_EOL." Invalid Commands: `red`" . join(" , ", $commands_unfitered)."`red` , write `light_green`\"php __path explain\"`light_green` to see list of available commands or create a new command with `light_green`\"php __path create command yourCommandName\"`light_green` ");
        }
    }
    public function loadDefaultCLIs()
    {
        //        add some default commands
        $this->commands["explain"]['class']               = $this;
        $this->commands["explain"]['entry']               = "getCommandsDetails";
        $this->commands["explain"]["description"]         = "This command shows all details about a CLI";
    }
    public function getCommandsDetails($argument)
    {
        echo PHP_EOL;
        if ($argument['explain'] === null) {//no command specified, show all commands and their explanations
            $mask2 = "%22.30s    %-30s".PHP_EOL;

            foreach ($this->commands as $cmd => $desc) {
                $this->write(["`light_green`{$cmd}`light_green`",$desc["description"]],'%22.30s    %-32.30s');
                $this->write(PHP_EOL);
                if (isset($desc["arguments"])) {
                    //                Out put all supported arguments
                    foreach ($desc["arguments"] as $arg => $value) {
                        $this->write(["`green`{$arg}`green`",$value['desc']],$mask2);
                    }
                }

                echo PHP_EOL . PHP_EOL;
            }
        } else {
            if (!isset($this->commands[$argument['explain']])) {
                $this->write("`light_red`!{$argument['explain']} not a recognized Command `light_red` You can create a custom CLI Command in path/Commands Folder");

            } else {
                $mask = "%32.30s    %30s".PHP_EOL;
                $this->write(["`light_green`{$argument['explain']}`light_green`",$this->commands[$argument['explain']]["description"]],'%32.30s    %-32.30s');
                $this->write(PHP_EOL.PHP_EOL);
                foreach ($this->commands[$argument['explain']]["arguments"] as $arg => $value) {
                    $this->write(["`green`{$arg}`green`",$value['desc']],$mask);
                }
                echo PHP_EOL . PHP_EOL;
            }
        }
        //        var_dump();
        //        var_dump($argument);
    }

    /**
     * @param $argument
     * @return mixed
     */
    protected function entry($argument)
    {
        // TODO: Implement entry() method.
    }
}
