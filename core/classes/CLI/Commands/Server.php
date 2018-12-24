<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/24/2018
 * @Time 12:16 PM
 * @Project Path
 */

namespace Path\Console;


use Path\Console;

class Server extends CLInterface
{
    public $name = "server";
    public $description = "start development server";
    public $arguments = [
        "start" => [
            "desc" => "starts server"
        ]
    ];

    public function entry(object $argument)
    {
        $cmd = "php -S localhost:8000 index.php";
        echo PHP_EOL;
        echo Console::build("---","green",false)." Server starts at: ".Console::build("localhost:8000",'light_green').PHP_EOL;
        echo Console::build("---","green",false)." You can use this as your proxy server in webpack".PHP_EOL.PHP_EOL;
        echo Console::build("---","green",false)." Press ^C to terminate";

        while (@ ob_end_flush()); // end all output buffers if any

        $proc = popen("$cmd 2>&1 ; echo Exit status : $?", 'r');

        $live_output     = "";
        $complete_output = "";

        while (!feof($proc))
        {
            $live_output     = fread($proc, 4096);
            $complete_output = $complete_output . $live_output;
            echo "$live_output";
            @ flush();
        }

        pclose($proc);

        // get exit status
        preg_match('/[0-9]+$/', $complete_output, $matches);

        // return exit status and intended output
        return array (
            'exit_status'  => intval(@$matches[0]),
            'output'       => str_replace("Exit status : " . @$matches[0], '', $complete_output)
        );
    }
}