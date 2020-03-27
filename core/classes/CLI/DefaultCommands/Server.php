<?php

/**
 * @Author by Sulaiman Adewale.
 * @Date 12/24/2018
 * @Time 12:16 PM
 * @Project Path
 */

namespace Path\Core\CLI\DefaultCommands;



use Path\Core\CLI\CInterface;
use Path\Plugins\PHPMailer\Exception;

class Server extends CInterface
{
    private const DEFAULT_PORT = 8080;
    private const DEFAULT_HOST = "localhost";
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

    private function getPort($port, $host)
    {
        $port = $port ?? config("PROJECT->port");
        $port = $port ? $port : self::DEFAULT_PORT;

        $port_valid = function ($port) use ($host):bool{
            if ($fp = @fsockopen($host, $port, $errno, $errstr, 3)) {
                fclose($fp);
                return false;
            }
            return true;
        };
        if (filter_var(
            $port,
            FILTER_VALIDATE_REGEXP,
            ["options" => ["regexp" => "/^([\d]{1,5})$/"]]
        )) {
            if ($port_valid($port)){
                return $port;
            }else{
                $suggested_port = rand(9000, 9999);
                $this->write("\n`white`Port $port is in use by another application! trying to use " . $suggested_port . "`white`\n");
                return $this->getPort($suggested_port, $host);
            }
        } else {
            $this->write("\n`light_red` PORT `light_red` `white`{$port}`white` `light_red`is invalid `light_red`\n");
            throw new Exception("Error occured");
        }
    }

    private function getHost($host)
    {
        $host = $host ?? config("PROJECT->host") ?? self::DEFAULT_HOST;
        if (!$host) {
            $this->write("\n`white`Empty/Invalid host! using " . self::DEFAULT_HOST . "`white`\n");
            return self::DEFAULT_HOST;
        } elseif ($host == 'localhost') {
            return $host;
        } elseif (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        } else {
            $this->write("\n`light_red` IP`light_red` `white`{$host}`white` `light_red`is invalid `light_red`\n");
            throw new Exception("Error occured");
        }
    }

    public function entry($argument)
    {
        $ip = $this->getHost($argument['--ip'] ?? null);
        $port = $this->getPort(@$argument['port'], $ip);
        $cmd = "cd " . ROOT_PATH . "/ && php -S {$ip}:{$port}";
        echo PHP_EOL;
        $this->write("`green`[+] Server started at: `green` {$ip}:{$port}" . PHP_EOL);
        $this->write(PHP_EOL . '`blue`Press ^C to terminate`blue`' . PHP_EOL);

        $os = strtolower(PHP_OS);

        if (strpos($os, 'win') !== false) {
            try {
                shell_exec("start http://{$ip}:{$port}");
            } catch (Exception $e) {
                //
            }
        } elseif (strpos($os, 'mac') !== false) {
            try {
                shell_exec("open http://{$ip}:{$port}");
            } catch (Exception $e) {
                //
            }
        } elseif (strpos($os, 'linux') !== false) {
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
