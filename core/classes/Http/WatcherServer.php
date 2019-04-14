<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/27/2019
 * @Time 3:45 PM
 * @Project Path
 */

namespace Path\Core\Http;


use Path\Plugins\PathSocket\Server;


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
