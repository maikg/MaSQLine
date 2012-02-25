<?PHP
namespace DBALExt\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use DBALExt\Queries\Query;

class WhereClause implements Clause {
  private $schema;
  
  private $conditions = array();
  private $params = array();
  private $types = array();
  
  
  public function __construct(Schema $schema) {
    $this->schema = $schema;
  }
  
  
  public function whereEquals($field_format, $value) {
    $this->addCondition($field_format, '=', $value);
  }
  
  
  public function whereNotEquals($field_format, $value) {
    $this->addCondition($field_format, '<>', $value);
  }
  
  
  public function whereGreaterThan($field_format, $value) {
    $this->addCondition($field_format, '>', $value);
  }
  
  
  public function whereSmallerThan($field_format, $value) {
    $this->addCondition($field_format, '<', $value);
  }
  
  
  public function whereGreaterThanOrEquals($field_format, $value) {
    $this->addCondition($field_format, '>=', $value);
  }
  
  
  public function whereSmallerThanOrEquals($field_format, $value) {
    $this->addCondition($field_format, '<=', $value);
  }
  
  
  public function whereIn($field_format, array $values) {
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
  }
  
  
  private function addCondition($field_format, $operator, $value, $type = NULL) {
    list($table_name, $column_name) = Query::convertFieldFormat($field_format);
    
    $this->conditions[] = sprintf("`%s`.`%s` %s ?", $table_name, $column_name, $operator);
    $this->params[] = $value;
    
    if ($type === NULL) {
      $type = $this->schema->getTable($table_name)->getColumn($column_name)->getType();
    }
    $this->addType($type);
  }
  
  
  private function addType($type) {
    if (is_string($type)) {
      $type = Type::getType($type);
    }
    $this->types[] = $type;
  }
  
  
  public function getParamValues() {
    return $this->params;
  }
  
  
  public function getParamTypes() {
    return $this->types;
  }
  
  
  public function isEmpty() {
    return (count($this->conditions) == 0);
  }
  
  
  public function toSQL() {
    return "WHERE " . implode(' AND ', $this->conditions);
  }
}
