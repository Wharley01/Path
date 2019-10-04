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
use Path\Plugins\PHPMailer\Exception;

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
        $argument = (object) $argument;
        $port = $this->getPort(@$argument->port);
        $cmd = "cd " . ROOT_PATH . "/ && php -S localhost:{$port}";
        echo PHP_EOL;
        $this->write("`green`[+] Server started at: `green` localhost:{$port}" . PHP_EOL);
        $this->write(PHP_EOL . '`blue`Press ^C to terminate`blue`' . PHP_EOL);
        var_dump(PHP_OS);

        if (mb_strpos(PHP_OS, 'win') !== false) {
            try {
                shell_exec("start http://localhost:{$port}");
            } catch (Exception $e) {
                // 
            }
        } elseif (mb_strpos(PHP_OS, 'mac')) {
            try {
                shell_exec("open http://localhost:{$port}");
            } catch (Exception $e) {
                // 
            }
        } elseif (mb_strpos(PHP_OS, 'linux')) {
            try {
                shell_exec("xdg-open http://localhost:{$port}");
            } catch (Exception $e) {
                // 
            }
        } else {
            try {
                shell_exec("chrome http://localhost:{$port}");
            } catch (Exception $e) {
                // 
            }
        }

        $proc = shell_exec($cmd);
    }
}
