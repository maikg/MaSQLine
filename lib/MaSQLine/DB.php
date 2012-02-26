<?PHP
namespace MaSQLine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use MaSQLine\Queries\SelectQuery;
use MaSQLine\Queries\InsertQuery;
use MaSQLine\Queries\UpdateQuery;
use MaSQLine\Queries\DeleteQuery;

/**
 * Provides a wrapper around a {@link \Doctrine\DBAL\Connection} instance and
 * a {@link \Doctrine\DBAL\Schema\Schema} instance. In addition, this class
 * provides factory methods that automatically inject the proper Connection
 * and Schema instances into the query's constructor.
 * 
 * @author Maik Gosenshuis
 * @see \Doctrine\DBAL\Connection
 * @see \Doctrine\DBAL\Schema\Schema
 */
class DB {
  private $conn;
  private $schema;
  
  
  public function __construct(Connection $conn, Schema $schema) {
    $this->conn = $conn;
    $this->schema = $schema;
  }
  
  
  public function getConnection() {
    return $this->conn;
  }
  
  
  public function getSchema() {
    return $this->schema;
  }
  
  
  public function createSelectQuery() {
    return new SelectQuery($this->conn, $this->schema);
  }
  
  
  public function createInsertQuery($table_name) {
    return new InsertQuery($this->conn, $this->schema, $table_name);
  }
  
  
  public function createUpdateQuery($table_name) {
    return new UpdateQuery($this->conn, $this->schema, $table_name);
  }
  
  
  public function createDeleteQuery($table_name) {
    return new DeleteQuery($this->conn, $this->schema, $table_name);
  }
  
  
  /**
   * Wrapper around {@link \Doctrine\DBAL\Connection\transactional()} that injects this
   * instance into the transaction closure instead of the Connection instance.
   *
   * @param \Closure $transaction A closure that receives this instance as its first argument. Should perform
   *                              all statements that comprise the transaction.
   * @return void
   * @throws Exception
   */
  public function transactional(\Closure $transaction) {
    $this->conn->beginTransaction();
    try {
      $transaction($this);
      $this->conn->commit();
    }
    catch (Exception $e) {
      $this->conn->rollback();
      throw $e;
    }
  }
  
  
  /**
   * Shortcut method that creates an instance of {@link \MaSQLine\Queries\InsertQuery}, sets
   * the necessary properties and executes the query.
   *
   * @param string $table_name 
   * @param array $values 
   * @return int The number of affected rows.
   */
  public function insert($table_name, array $values) {
    return $this->createInsertQuery($table_name)
      ->setValues($values)
      ->execute();
  }
}
