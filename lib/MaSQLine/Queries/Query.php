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
  
  
  protected function registerTableAlias($table_name, $alias) {
    $this->table_aliases[$alias] = $table_name;
  }
  
  
  public function getRealTableName($alias) {
    if (!array_key_exists($alias, $this->table_aliases)) {
      return $alias;
    }
    
    return $this->table_aliases[$alias];
  }
}
