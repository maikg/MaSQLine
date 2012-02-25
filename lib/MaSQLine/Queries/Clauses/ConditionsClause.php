<?PHP
namespace MaSQLine\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use MaSQLine\Queries\Query;

class ConditionsClause extends Clause {
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
  
  
  private function addCondition($condition, array $params = array(), array $types = array()) {
    $this->conditions[] = $condition;
    $this->params[] = $params;
    $this->types[] = $types;
  }
  
  
  public function orGroup(\Closure $setup_where) {
    $where = new ConditionsClause($this->schema, 'OR');
    $setup_where = $setup_where($where);
    $this->addCondition($where->toSQL(), $where->getParamValues(), $where->getParamTypes());
    
    return $this;
  }
  
  
  public function andGroup(\Closure $setup_where) {
    $where = new ConditionsClause($this->schema);
    $setup_where = $setup_where($where);
    $this->addCondition($where->toSQL(), $where->getParamValues(), $where->getParamTypes());
    
    return $this;
  }
  
  
  public function equals($field_format, $value) {
    return $this->addSimpleCondition($field_format, '=', $value);
  }
  
  
  public function notEquals($field_format, $value) {
    return $this->addSimpleCondition($field_format, '<>', $value);
  }
  
  
  public function equalColumns($left_field_format, $right_field_format) {
    list($left_table_name, $left_column_name) = Query::convertFieldFormat($left_field_format);
    list($right_table_name, $right_column_name) = Query::convertFieldFormat($right_field_format);
    
    $condition = sprintf(
      "`%s`.`%s` = `%s`.`%s`",
      $left_table_name,
      $left_column_name,
      $right_table_name,
      $right_column_name
    );
    $this->addCondition($condition);
    
    return $this;
  }
  
  
  public function greaterThan($field_format, $value) {
    return $this->addSimpleCondition($field_format, '>', $value);
  }
  
  
  public function smallerThan($field_format, $value) {
    return $this->addSimpleCondition($field_format, '<', $value);
  }
  
  
  public function greaterThanOrEquals($field_format, $value) {
    return $this->addSimpleCondition($field_format, '>=', $value);
  }
  
  
  public function smallerThanOrEquals($field_format, $value) {
    return $this->addSimpleCondition($field_format, '<=', $value);
  }
  
  
  public function like($field_format, $value) {
    return $this->addSimpleCondition($field_format, 'LIKE', $value);
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
    
    $this->addCondition(
      sprintf("`%s`.`%s` IN (?)", $table_name, $column_name),
      array($values),
      array($type)
    );
    
    return $this;
  }
  
  
  private function addSimpleCondition($field_format, $operator, $value, $type = NULL) {
    list($table_name, $column_name) = Query::convertFieldFormat($field_format);
    
    if ($type === NULL) {
      $type = $this->schema->getTable($table_name)->getColumn($column_name)->getType();
    }
    
    $this->addCondition(
      sprintf("`%s`.`%s` %s ?", $table_name, $column_name, $operator),
      array($value),
      array(is_string($type) ? Type::getType($type) : $type)
    );
    
    return $this;
  }
  
  
  public function getParamValues() {
    $params = array();
    foreach ($this->params as $condition_params) {
      $params = array_merge($params, $condition_params);
    }
    return $params;
  }
  
  
  public function getParamTypes() {
    $types = array();
    foreach ($this->types as $condition_types) {
      $types = array_merge($types, $condition_types);
    }
    return $types;
  }
  
  
  public function isEmpty() {
    return (count($this->conditions) == 0);
  }
  
  
  public function toSQL() {
    $condition_strings = array_map(function($condition) {
      if ($condition instanceof ConditionsClause) {
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
