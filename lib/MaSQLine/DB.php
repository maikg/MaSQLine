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
  private static $instances = array();
  private static $default_alias = NULL;
  
  
  public static function register($alias, $db, $default = false) {
    self::$instances[$alias] = $db;
    
    if ($default || count(self::$instances) == 1) {
      self::$default_alias = $alias;
    }
  }
  
  
  public static function deregister($alias) {
    unset(self::$instances[$alias]);
    
    if ($alias == self::$default_alias) {
      self::$default_alias = NULL;
    }
  }
  
  
  public static function get($alias) {
    if (!array_key_exists($alias, self::$instances)) {
      throw new \InvalidArgumentException(sprintf("No connection registered with alias '%s'.", $alias));
    }
    
    if (is_callable(self::$instances[$alias])) {
      self::$instances[$alias] = call_user_func(self::$instances[$alias]);
    }
    
    return self::$instances[$alias];
  }
  
  
  public static function getDefault() {
    if (self::$default_alias === NULL) {
      throw new \RuntimeException("No default database is set.");
    }
    
    return self::get(self::$default_alias);
  }
  
  
  private $conn;
  private $schema;
  
  private $expression_builder;
  
  
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
  
  
  public function simpleSelect($table_name, array $conditions = array()) {
    $query = $this->createSelectQuery()
      ->select(sprintf('%s.*', $table_name))
      ->from($table_name);
    
    $this->applyEqualsConditionsToQuery($query, $table_name, $conditions);
    
    return $query;
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
  
  
  public function update($table_name, array $values, array $conditions = array()) {
    $query = $this->createUpdateQuery($table_name)
      ->setValues($values);
    $this->applyEqualsConditionsToQuery($query, $table_name, $conditions);
    return $query->execute();
  }
  
  
  public function delete($table_name, array $conditions = array()) {
    $query = $this->createDeleteQuery($table_name);    
    $this->applyEqualsConditionsToQuery($query, $table_name, $conditions);
    return $query->execute();
  }
  
  
  private function applyEqualsConditionsToQuery($query, $table_name, array $conditions) {
    if (count($conditions) == 0) {
      return;
    }
    
    $builder = $query->expr();
    
    $conditions = array_map(function($key, $value) use ($table_name, $builder) {
      $col_path = sprintf('%s.%s', $table_name, $key);
      return $builder->eq($col_path, $value);
    }, array_keys($conditions), $conditions);
    
    $expr = call_user_func_array(array($builder, 'all'), $conditions);
    
    $query->where($expr);
  }
}
