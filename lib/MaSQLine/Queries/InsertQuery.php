<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class InsertQuery extends ManipulationQuery {
  private $table_name;
  private $values = array();
  
  
  public function __construct(Connection $conn, Schema $schema, $table_name) {
    parent::__construct($conn, $schema);
    
    $this->table_name = $table_name;
  }
  
  
  public function setValues(array $values) {
    $this->values = array_merge($this->values, $values);
    return $this;
  }
  
  
  public function toSQL() {
    if (count($this->values) == 0) {
      throw new \InvalidArgumentException("No values set.");
    }
    
    $column_names = array_keys($this->values);
    
    $quoted_column_names = array_map(function($column_name) {
      return sprintf("`%s`", $column_name);
    }, $column_names);
    
    $placeholders = array_fill(0, count($column_names), '?');
    
    return sprintf(
      "INSERT INTO `%s` (%s) VALUES (%s)",
      $this->table_name,
      implode(', ', $quoted_column_names),
      implode(', ', $placeholders));
  }
  
  
  public function getParamValues() {
    return array_values($this->values);
  }
  
  
  public function getParamTypes() {
    $table_name = $this->table_name;
    $schema = $this->schema;
    return array_map(function($column_name) use ($schema, $table_name) {
      return $schema->getTable($table_name)->getColumn($column_name)->getType();
    }, array_keys($this->values));
  }
}
