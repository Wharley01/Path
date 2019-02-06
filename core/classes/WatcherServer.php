<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/27/2019
 * @Time 3:45 PM
 * @Project Path
 */

namespace Path;


use Path\Plugins\PathSocket\Server;
import(
    "core/Plugins/PathSocket/src/Server"
);

class WatcherServer extends Server
{

    protected $host;
    protected $port;
    public function __construct()
    {
        $this->host = config("WEBSOCKET->host");
        $this->port = config("WEBSOCKET->port");
        parent::__construct();
    }

}