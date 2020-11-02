<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 4/13/2019
 * @Time 7:40 PM
 * @Project path
 */

namespace Path\Core\Error\Exceptions;

use Path\Core\Http\Response;
use Throwable;

class ResponseError extends \Exception
{
    protected $response;
    public function __construct($message = "", $code = 0, Throwable $previous = null,$response = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Response
     */
    public function getResponse():?Response
    {
        return $this->response;
    }
    // custom string representation of object
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
