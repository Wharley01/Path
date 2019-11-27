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
    private const DEFAULT_IP = "localhost";
    public $name = "server";

    public $description = "start development server";

    public $arguments = [
        "start" => [
            "desc" => "starts server"
        ],
        "port"  => [
            "desc" => "port to use"
        ],
        "--ip" => [
            "desc" => "IP to use"
        ]
    ];

    private function getPort($port)
    {
        if (!$port) {
            return self::DEFAULT_PORT;
        } elseif (@filter_var(
            $port,
            FILTER_VALIDATE_REGEXP,
            ["options" => ["regexp" => "/^([\d]{1,5})$/"]]
        )) {
            return $port;
        } else {
            $this->write("\n`light_red` PORT `light_red` `white`{$port}`white` `light_red`is invalid `light_red`\n");
            throw new Exception("Error occured");
        }
    }

    private function getIp($ip)
    {
        if(!$ip)
            return self::DEFAULT_IP;

        if (@filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        } else {
            $this->write("\n`light_red` IP`light_red` `white`{$ip}`white` `light_red`is invalid `light_red`\n");
            throw new Exception("Error occured");
        }
    }

    public function entry($argument)
    {
        $port = $this->getPort(@$argument['port']);
        $ip = $this->getIp($argument['--ip'] ?? null);
        $cmd = "cd " . ROOT_PATH . "/ && php -S {$ip}:{$port}";
        echo PHP_EOL;
        $this->write("`green`[+] Server started at: `green` {$ip}:{$port}" . PHP_EOL);
        $this->write(PHP_EOL . '`blue`Press ^C to terminate`blue`' . PHP_EOL);

        if (mb_strpos(PHP_OS, 'win') !== false) {
            try {
                shell_exec("start http://{$ip}:{$port}");
            } catch (Exception $e) {
                //
            }
        } elseif (mb_strpos(PHP_OS, 'mac')) {
            try {
                shell_exec("open http://{$ip}:{$port}");
            } catch (Exception $e) {
                //
            }
        } elseif (mb_strpos(PHP_OS, 'linux')) {
            try {
                shell_exec("xdg-open http://{$ip}:{$port}");
            } catch (Exception $e) {
                //
            }
        } else {
            try {
                shell_exec("chrome http://{$ip}:{$port}");
            } catch (Exception $e) {
                //
            }
        }

        $proc = shell_exec($cmd);
    }
}
