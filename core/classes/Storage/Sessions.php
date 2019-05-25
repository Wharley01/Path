<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/29/2018
 * @Time 7:21 AM
 * @Project Path
 */

namespace Path\Core\Storage;


class Sessions
{
    private  $session_id = null;

    public function __construct($session_id = null)
    {

        if (!is_null($session_id)) {
            if (!preg_match("/^[-,a-zA-Z0-9]{1,128}$/", $session_id) or strlen($session_id) < 5 or !preg_match('/[0-9]/', $session_id)) {
                $this->session_id = bin2hex(openssl_random_pseudo_bytes(32));
            } else {
                $this->session_id = $session_id;
            }
        }
    }

    private function close()
    {
        if (!is_null($this->session_id)) {
            session_write_close();
        }
    }

    private function start()
    {
        if (!is_null($this->session_id)) {
            session_write_close();
            session_id($this->session_id);
            session_start();
            //            echo "using ID";
        }
    }
    public function store($key, $value)
    {
        $this->start();
        $_SESSION[$key] = $value;
        $this->close();
    }

    public function overwrite($key, $value)
    {
        $this->start();
        $_SESSION[$key] = $value;
        $this->close();
    }

    public function get($key)
    {
        $this->start();
        $value = @$_SESSION[$key];
        $this->close();
        return $value ?? null;
    }
    public function delete($key)
    {
        $this->start();
        unset($_SESSION[$key]);
        $this->close();
    }

    public function exists($key){
        return isset($_SESSION[$key]);
    }

    public function getAll(){
        return $_SESSION ?? [];
    }
}
