<?php
namespace Connection;
class Connect{
  private $HOST = "localhost";
  private $USER = "";
  private $PASSWORD = "";
  private $DATABASE = "";
  public $connection = null;
  function __construct(){
    try {
$this->connection = new PDO("mysql:host={$this->HOST};dbname={$this->DATABASE}", $this->USER, $this->PASSWORD);
    } catch (Exception $e) {
      die($e->getMessage());
    }
return $this->connection;

  }
}
?>
