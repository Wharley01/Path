<?php


namespace Path\Core\CLI\DefaultCommands;


use Path\Core\CLI\CInterface;
use Path\Core\Http\Watcher\Server;

class Watcher extends CInterface
{


    /*
     * Command Line name
     *
     * @var String
     * */
    public $name = "watcher";
    public $description = "Start Watcher Server";


    public $arguments = [
        "start" => [
            "desc" => "start watcher"
        ]
    ];



    public function __construct()
    { }

    /**
     * @param $params
     * @return mixed|void
     */
    public function entry($params)
    {
        $server = new Server();
    }
}
