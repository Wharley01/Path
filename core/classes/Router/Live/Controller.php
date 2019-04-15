<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/17/2019
 * @Time 12:59 AM
 * @Project Path
 */

namespace Path\Core\Router\Live;


use Path\Core\Http\Watcher;
use Path\Core\Storage\Sessions;

abstract class Controller
{
    public function onMessage(
        &$watcher,
        Sessions $sessions,
        ?String  $message
    ){

    }

    public function onConnect(
        &$watcher,
        Sessions $sessions,
        ?String  $message
    ){

    }

    public function onClose(
        &$watcher,
        Sessions $sessions,
        ?String  $message
    ){

    }

}
