<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/17/2019
 * @Time 12:59 AM
 * @Project Path
 */

namespace Path\Core\Router\Live;


use Path\Core\Http\Watcher\WatcherInterface;
use Path\Core\Storage\Sessions;

abstract class Controller
{



    public function onMessage(
        WatcherInterface &$watcher,
        Sessions $sessions

    ){

    }

    public function onConnect(
        WatcherInterface &$watcher,
        Sessions $sessions
    ){

    }

    public function onClose(
        WatcherInterface &$watcher,
        Sessions $sessions
    ){

    }

}
