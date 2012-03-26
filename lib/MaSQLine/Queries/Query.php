<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

abstract class Query extends Expression {  
  protected $conn;
  protected $schema;
  
  private $expression_builder;
  
  private $table_aliases = array();
  
  
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
  
  
  public function __toString() {
    return $this->getFormat();
  }
  
  
  public function toSQL() {
    return $this->getFormat();
  }
  
  
  public function getExpressionBuilder() {
    if ($this->expression_builder === NULL) {
      $this->expression_builder = new ExpressionBuilder($this);
    }
    
    return $this->expression_builder;
  }
  
  
  public function expr() {
    return $this->getExpressionBuilder();
  }
  
  
  public function table($table_name, $alias = NULL) {
    if ($alias === NULL) {
      return $table_name;
    }
    
    $this->registerTableAlias($table_name, $alias);
    return array($table_name => $alias);
  }
  
  
  protected function registerTableAlias($table_name, $alias) {
    $this->table_aliases[$alias] = $table_name;
  }
  
  
  public function getRealTableName($table_name) {
    if (!array_key_exists($table_name, $this->table_aliases)) {
      return $table_name;
    }
    
    return $this->table_aliases[$table_name];
  }
}
