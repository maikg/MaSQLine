<?PHP
namespace MaSQLine\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use MaSQLine\Queries\Query;
use MaSQLine\Queries\ConditionsBuilder;
use MaSQLine\Queries\ColumnPath;

class FromClause extends Clause {
  private $schema;
  
  private $table_name;
  
  private $joins = array();
  
  
  public function __construct($schema) {
    $this->schema = $schema;
  }
  
  
  public function setTableName($table_name) {
    $this->table_name = $table_name;
  }
  
  
  public function addInnerJoin($origin, $target) {
    $this->addJoin('INNER JOIN', $origin, $target);
  }
  
  
  public function addLeftJoin($origin, $target) {
    $this->addJoin('LEFT JOIN', $origin, $target);
  }
  
  
  private function addJoin($join_prefix, $origin, $target) {
    $conditions_clause = new ConditionsClause('ON');
    $builder = new ConditionsBuilder($this->schema);
    
    if ($target instanceof \Closure) {
      $target_table_name = $origin;
      $expr = $target($builder);
    }
    else {
      $target_table_name = ColumnPath::parse($this->schema, $target)->getTable()->getName();
      $expr = $builder->eqCol($origin, $target);
    }
    
    $conditions_clause->setExpression($expr);
    
    $this->joins[] = array($join_prefix, $target_table_name, $conditions_clause);
  }
  
  
  public function isEmpty() {
    return ($this->table_name === NULL);
  }
  
  
  public function getParamValues() {
    $params = array();
    foreach ($this->joins as $join) {
      $conditions = $join[2];
      $params = array_merge($params, $conditions->getParamValues());
    }
    return $params;
  }
  
  
  public function getParamTypes() {
    $types = array();
    foreach ($this->joins as $join) {
      $conditions = $join[2];
      $types = array_merge($types, $conditions->getParamTypes());
    }
    return $types;
  }
  
  
  public function toSQL() {
    if (empty($this->table_name)) {
      throw new \RuntimeException("Expected a table name to be set.");
    }
    
    $lines = array_map(function($join) {
      list($join_type, $target_table_name, $conditions) = $join;
      return sprintf("%s `%s` %s", $join_type, $target_table_name, $conditions->toSQL());
    }, $this->joins);
    
    array_unshift($lines, sprintf("FROM `%s`", $this->table_name));
    
    return implode("\n", $lines);
  }
}
