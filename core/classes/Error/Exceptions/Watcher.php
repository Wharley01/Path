<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 4/13/2019
 * @Time 7:47 PM
 * @Project path
 */

namespace Path\Core\Error\Exceptions;


use Throwable;

class Watcher extends \Exception
{
    public $path_template;
    public $real_path;
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    // custom string representation of object
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
