<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 5/20/2019
 * @Time 2:24 AM
 * @Project path
 */

namespace Path\Core\Http\Watcher;


interface WatcherInterface
{

    public function close($message = null);
    public function changesOccurred():bool;
    public function getMessage():?String;
    public function getParams($key = null);
}