<?php

namespace Path\Core\Error;

use Path\Core\File\File;

class Logger
{
    private const LOG_PATH = "path/.Storage/.LOGS.txt";

    public static function log(string $message,$trace = null){
        $logs = File::file(ROOT_PATH.self::LOG_PATH);

        if($logs->getContent())
            $logs->writeLn($message,FILE_APPEND);
        else
            $logs->write($message, FILE_APPEND);
    }
}
