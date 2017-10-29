<?php
namespace Connection;

/**
 *
 */
interface DB{
  public function __construct();
  public function close();
}

class Mysql implements DB
{
    private $HOST = "localhost";
    private $USER = "";
    private $PASSWORD = "";
    private $DATABASE = "";
    public $connection = null;

    public function __construct()
    {
        try {
            $this->connection = new \PDO("mysql:host={$this->HOST};dbname={$this->DATABASE}", $this->USER, $this->PASSWORD);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
        return $this->connection;

    }

    public function close()
    {
        $this->Connection = null;
    }
}
?>
