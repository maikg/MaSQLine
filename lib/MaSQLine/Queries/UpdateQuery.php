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
    $this->where_clause = new Clauses\ConditionsClause($this->schema, 'AND');
  }
  
  
  public function setValues(array $values) {
    $this->values = array_merge($this->values, $values);
    
    return $this;
  }
  
  
  public function where(\Closure $setup_where) {
    $setup_where($this->where_clause);
    
    return $this;
  }
  
  
  public function toSQL() {
    if ($this->where_clause->isEmpty()) {
      return sprintf(
        "UPDATE `%s` SET %s",
        $this->table_name,
        implode(', ', $this->getSetExpressions())
      );
    }
    
    return sprintf(
      "UPDATE `%s` SET %s WHERE %s",
      $this->table_name,
      implode(', ', $this->getSetExpressions()),
      $this->where_clause->toSQL()
    );
  }
  
  
  private function getSetExpressions() {
    $expressions = array();
    foreach ($this->values as $column_name => $value) {
      $expressions[] = sprintf("%s = ?", Query::quoteFieldFormat($column_name));
    }
    return $expressions;
  }
  
  
  public function getParamValues() {
    return array_merge(array_values($this->values), $this->where_clause->getParamValues());
  }
  
  
  public function getParamTypes() {
    $schema = $this->schema;
    $table_name = $this->table_name;
    $types = array_map(function($column_name) use ($schema, $table_name) {
      return $schema->getTable($table_name)->getColumn($column_name)->getType();
    }, array_keys($this->values));
    
    return array_merge($types, $this->where_clause->getParamTypes());
  }
}
