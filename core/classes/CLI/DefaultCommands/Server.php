<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/24/2018
 * @Time 12:16 PM
 * @Project Path
 */

namespace Path\Core\CLI\DefaultCommands;



use Path\Core\CLI\CInterface;
use Path\Core\CLI\Console;

class Server extends CInterface
{
    private const DEFAULT_PORT = 8080;
    public $name = "server";

    public $description = "start development server";

    public $arguments = [
        "start" => [
            "desc" => "starts server"
        ],
        "port"  => [
            "desc" => "port to use"
        ]
    ];

    private function getPort($port)
    {
        return $port ?? self::DEFAULT_PORT;
    }

    public function entry($argument)
    {
        $argument = (object)$argument;
        $port = $this->getPort(@$argument->port);
        $cmd = "php -S localhost:{$port} index.php";
        echo PHP_EOL;
        $this->write("`green`[+] Server started at: `green` localhost:{$port}".PHP_EOL);
        $this->write(PHP_EOL.'`blue`Press ^C to terminate`blue`'.PHP_EOL);

        $proc = popen("$cmd 2>&1 ; echo Exit status : $?", 'r');

    }
}
