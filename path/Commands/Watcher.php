<?php


namespace Path\Console;


use Path\Console;
use Path\WatcherServer;

import("core/Classes/WatcherServer");
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
    {
    }

    /**
     * @param $params
     */
    public function entry(object $params)
    {
        $server = new WatcherServer();
    }

}
        