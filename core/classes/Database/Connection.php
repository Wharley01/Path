<?php
namespace Path\Database\Connection;

/**
 *
 */
interface DB{
  public function __construct();
  public function close();
}

class Mysql implements DB
{
    public $connection = null;

    public function __construct()
    {
        $HOST           = config("DATABASE->host");
        $USER           = config("DATABASE->user");
        $NAME           = config("DATABASE->name");
        $PASSWORD       = config("DATABASE->pass");

        try {
            $opt = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->connection = new \PDO("mysql:host={$HOST};dbname={$NAME}", $USER, $PASSWORD,$opt);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
        return $this->connection;
    }

    public function close()
    {
        $this->connection = null;
    }
    public function __destruct()
    {
        $this->connection = null;
    }


}
?>
