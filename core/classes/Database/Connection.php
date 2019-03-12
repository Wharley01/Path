<?php
namespace Path\Database\Connection;

/**
 *
 */
interface DB{
  public function __construct();
  public static function close();
}

class Mysql implements DB
{
    public $connection = null;
    private static $conn = null;

    public function __construct()
    {
//        $HOST           = config("DATABASE->host");
//        $USER           = config("DATABASE->user");
//        $NAME           = config("DATABASE->name");
//        $PASSWORD       = config("DATABASE->pass");
//
//        try {
//            $opt = [
//                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
//                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
//                \PDO::ATTR_EMULATE_PREPARES   => false,
//            ];
//            $this->connection = new \PDO("mysql:host={$HOST};dbname={$NAME}", $USER, $PASSWORD,$opt);
//            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//        } catch (\PDOException $e) {
//            die($e->getMessage());
//        }
//        return $this->connection;
    }

    public static function connection(){
        $HOST           = config("DATABASE->host");
        $USER           = config("DATABASE->user");
        $NAME           = config("DATABASE->name");
        $PASSWORD       = config("DATABASE->pass");
        if(self::$conn === null){
            try {
                $opt = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$conn = new \PDO("mysql:host={$HOST};dbname={$NAME}", $USER, $PASSWORD,$opt);
                self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                return self::$conn;
            } catch (\PDOException $e) {
                die($e->getMessage());
            }
        }elseif(self::$conn instanceof \PDO){
            return self::$conn;
        }

    }

    public static function close()
    {
        self::$conn = null;
    }
    public function __destruct()
    {
        $this->connection = null;
    }


}
?>
