<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/21/2019
 * @Time 11:37 PM
 * @Project Path
 */

namespace Path\Plugins\PathSocket;


class Client
{
    public $type = "client";
    public $id;
    public $socket = null;
    public $has_done_handshake = false;
    public $partialBuffer = null;
    public $watching = null;
    public $response;
    public $headers;
    public $sendingContinues = false;
    public $is_closed = false;

}