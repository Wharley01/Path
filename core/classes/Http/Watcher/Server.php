<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/27/2019
 * @Time 3:45 PM
 * @Project Path
 */

namespace Path\Core\Http\Watcher;
use Path\Plugins\PathSocket;

class Server extends PathSocket\Server
{

    protected $host;
    protected $port;
    public function __construct()
    {
        $this->host = config("WATCHER->WEBSOCKET->host");
        $this->port = config("WATCHER->WEBSOCKET->port");
        parent::__construct();
    }
}
