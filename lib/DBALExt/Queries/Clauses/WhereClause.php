<?PHP
namespace DBALExt\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use DBALExt\Queries\Query;

class WhereClause implements Clause {
  private $schema;
  private $logical_operator;
  private $prefix;
  
  private $conditions = array();
  private $params = array();
  private $types = array();
  
  
  public function __construct(Schema $schema, $logical_operator = 'AND', $prefix = NULL) {
    $this->schema = $schema;
    $this->logical_operator = $logical_operator;
    $this->prefix = $prefix;
  }
  
  
  public function orWhere(\Closure $setup_where) {
    $where = new WhereClause($this->schema, 'OR');
    $setup_where = $setup_where($where);
    $this->conditions[] = $where;
    
    return $this;
  }
  
  
  public function andWhere(\Closure $setup_where) {
    $where = new WhereClause($this->schema);
    $setup_where = $setup_where($where);
    $this->conditions[] = $where;
    
    return $this;
  }
  
  
  public function equals($field_format, $value) {
    return $this->addCondition($field_format, '=', $value);
  }
  
  
  public function notEquals($field_format, $value) {
    return $this->addCondition($field_format, '<>', $value);
  }
  
  
  public function greaterThan($field_format, $value) {
    return $this->addCondition($field_format, '>', $value);
  }
  
  
  public function smallerThan($field_format, $value) {
    return $this->addCondition($field_format, '<', $value);
  }
  
  
  public function greaterThanOrEquals($field_format, $value) {
    return $this->addCondition($field_format, '>=', $value);
  }
  
  
  public function smallerThanOrEquals($field_format, $value) {
    return $this->addCondition($field_format, '<=', $value);
  }
  
  
  public function like($field_format, $value) {
    return $this->addCondition($field_format, 'LIKE', $value);
  }
  
  
  public function in($field_format, array $values) {
    list($table_name, $column_name) = Query::convertFieldFormat($field_format);
    
    switch ($this->schema->getTable($table_name)->getColumn($column_name)->getType()->getName()) {
      case 'integer':
      case 'smallint':
        $type = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        break;
      
      default:
        $type = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
    }
    
    $this->conditions[] = sprintf("`%s`.`%s` IN (?)", $table_name, $column_name);
    $this->params[] = $values;
    $this->addType($type);
    
    return $this;
  }
  
  
  private function addCondition($field_format, $operator, $value, $type = NULL) {
    list($table_name, $column_name) = Query::convertFieldFormat($field_format);
    
    $this->conditions[] = sprintf("`%s`.`%s` %s ?", $table_name, $column_name, $operator);
    $this->params[] = $value;
    
    if ($type === NULL) {
      $type = $this->schema->getTable($table_name)->getColumn($column_name)->getType();
    }
    $this->addType($type);
    
    return $this;
  }
  
  
  private function addType($type) {
    if (is_string($type)) {
      $type = Type::getType($type);
    }
    $this->types[] = $type;
  }
  
  
  public function getParamValues() {
    $remaining_params = $this->params;
    $params = array();
    
    foreach ($this->conditions as $condition) {
      if ($condition instanceof WhereClause) {
        $params = array_merge($params, $condition->getParamValues());
      }
      else {
        $params[] = array_shift($remaining_params);
      }
    }
    
    assert('count($remaining_params) == 0');
    
    return $params;
  }
  
  
  public function getParamTypes() {
    $remaining_types = $this->types;
    $types = array();
    
    foreach ($this->conditions as $condition) {
      if ($condition instanceof WhereClause) {
        $types = array_merge($types, $condition->getParamTypes());
      }
      else {
        $types[] = array_shift($remaining_types);
      }
    }
    
    assert('count($remaining_types) == 0');
    
    return $types;
  }
  
  
  public function isEmpty() {
    return (count($this->conditions) == 0);
  }
  
  
  public function toSQL() {
    $condition_strings = array_map(function($condition) {
      if ($condition instanceof WhereClause) {
        return $condition->toSQL();
      }
      return $condition;
    }, $this->conditions);
    
    if (count($condition_strings) == 1) {
      $conditions = $condition_strings[0];
    }
    else {
      $conditions = sprintf("(%s)", implode(sprintf(" %s ", $this->logical_operator), $condition_strings));
    }
    
    if ($this->prefix !== NULL) {
      $conditions = sprintf("%s %s", $this->prefix, $conditions);
    }
    
    return $conditions;
  }
}
