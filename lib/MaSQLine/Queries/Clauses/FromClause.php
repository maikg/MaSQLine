<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Expression;
use MaSQLine\Queries\Query;
use MaSQLine\Queries\ExpressionBuilder;
use MaSQLine\Queries\ColumnPath;

class FromClause extends Expression {
  private $query;
  
  private $table_name;
  
  private $alias;
  
  private $joins = array();
  
  
  public function __construct(Query $query) {
    $this->query = $query;
  }
  
  
  public function setTableName($table_name, $alias = NULL) {
    $this->table_name = $table_name;
    $this->alias = $alias;
  }
  
  
  public function addInnerJoin($target_table, Expression $join_conditions, $table_alias = NULL) {
    $this->addJoin('INNER JOIN', $target_table, $join_conditions, $table_alias);
  }
  
  
  public function addLeftJoin($target_table, Expression $join_conditions, $table_alias = NULL) {
    $this->addJoin('LEFT JOIN', $target_table, $join_conditions, $table_alias);
  }
  
  
  private function addJoin($join_prefix, $target_table, Expression $join_conditions, $table_alias = NULL) {
    $conditions_clause = new ConditionsClause($this->query, 'ON');
    $conditions_clause->setConditionsExpression($join_conditions);
    $this->joins[] = array($join_prefix, $target_table, $conditions_clause, $table_alias);
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
    
    $lines = array_map(function($join) {
      list($join_type, $target_table_name, $conditions, $table_alias) = $join;
      
      if ($table_alias === NULL) {
        return sprintf("%s `%s` %s", $join_type, $target_table_name, $conditions->getFormat());
      }
      
      return sprintf("%s `%s` AS `%s` %s", $join_type, $target_table_name, $table_alias, $conditions->getFormat());
    }, $this->joins);
    
    $table_expr = ($this->alias === NULL) ? sprintf('`%s`', $this->table_name) :
      sprintf('`%s` AS `%s`', $this->table_name, $this->alias);
    array_unshift($lines, sprintf("FROM %s", $table_expr));
    
    return implode("\n", $lines);
  }
}
