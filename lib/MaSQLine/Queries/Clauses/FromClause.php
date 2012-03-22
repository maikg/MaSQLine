<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Expression;
use MaSQLine\Queries\Query;
use MaSQLine\Queries\ExpressionBuilder;
use MaSQLine\Queries\ColumnPath;

class FromClause extends Expression {
  private $query;
  
  private $table_name;
  
  private $joins = array();
  
  
  public function __construct(Query $query) {
    $this->query = $query;
  }
  
  
  public function setTableName($table_name) {
    $this->table_name = $table_name;
  }
  
  
  public function addInnerJoin($target_table, Expression $join_conditions) {
    $this->addJoin('INNER JOIN', $target_table, $join_conditions);
  }
  
  
  public function addLeftJoin($target_table, Expression $join_conditions) {
    $this->addJoin('LEFT JOIN', $target_table, $join_conditions);
  }
  
  
  private function addJoin($join_prefix, $target_table, Expression $join_conditions) {
    $conditions_clause = new ConditionsClause($this->query, 'ON');
    $conditions_clause->setConditionsExpression($join_conditions);
    $this->joins[] = array($join_prefix, $target_table, $conditions_clause);
  }
  
  
  public function getValues() {
    $params = array();
    foreach ($this->joins as $join) {
      $conditions = $join[2];
      $params = array_merge($params, $conditions->getValues());
    }
    return $params;
  }
  
  
  public function getTypes() {
    $types = array();
    foreach ($this->joins as $join) {
      $conditions = $join[2];
      $types = array_merge($types, $conditions->getTypes());
    }
    return $types;
  }
  
  
  public function getFormat() {    
    if ($this->table_name === NULL) {
      return '';
    }
    
    if (empty($this->table_name)) {
      throw new \RuntimeException("Expected a table name to be set.");
    }
    
    $lines = array_map(function($join) {
      list($join_type, $target_table_name, $conditions) = $join;
      return sprintf("%s `%s` %s", $join_type, $target_table_name, $conditions->getFormat());
    }, $this->joins);
    
    array_unshift($lines, sprintf("FROM `%s`", $this->table_name));
    
    return implode("\n", $lines);
  }
}
