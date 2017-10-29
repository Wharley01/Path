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
    private $DATABASE = "test_bootsrap";
    public $connection = null;

    public function __construct()
    {
        try {
            $opt = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->connection = new \PDO("mysql:host={$this->HOST};dbname={$this->DATABASE}", $this->USER, $this->PASSWORD,$opt);
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
