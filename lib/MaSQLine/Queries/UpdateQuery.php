<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class UpdateQuery extends ManipulationQuery {
  private $table_name;
  private $where_clause;
  private $values = array();
  
  
  public function __construct(Connection $conn, Schema $schema, $table_name) {
    parent::__construct($conn, $schema);
    
    $this->table_name = $table_name;
    $this->where_clause = new Clauses\ConditionsClause('WHERE');
  }
  
  
  public function setValues(array $values) {
    $this->values = array_merge($this->values, $values);
    
    return $this;
  }
  
  
  public function where(Expression $expr) {
    $this->where_clause->setConditionsExpression($expr);
        
    return $this;
  }
  
  
  public function getFormat() {
    if ($this->where_clause->isEmpty()) {
      return sprintf(
        "UPDATE `%s` SET %s",
        $this->table_name,
        implode(', ', $this->getSetExpressions())
      );
    }
    
    return sprintf(
      "UPDATE `%s` SET %s %s",
      $this->table_name,
      implode(', ', $this->getSetExpressions()),
      $this->where_clause->getFormat()
    );
  }
  
  
  private function getSetExpressions() {
    $expressions = array();
    foreach ($this->values as $column_name => $value) {
      $column_path = sprintf('%s.%s', $this->table_name, $column_name);
      $expressions[] = sprintf("%s = ?", ColumnPath::parse($this->schema, $column_path)->toColumnString());
    }
    return $expressions;
  }
  
  
  public function getValues() {
    return array_merge(array_values($this->values), $this->where_clause->getValues());
  }
  
  
  public function getTypes() {
    $schema = $this->schema;
    $table_name = $this->table_name;
    $types = array_map(function($column_name) use ($schema, $table_name) {
      return $schema->getTable($table_name)->getColumn($column_name)->getType();
    }, array_keys($this->values));
    
    return array_merge($types, $this->where_clause->getTypes());
  }
}
