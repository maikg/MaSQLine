<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Query;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class SelectClause extends Clause {
  private $schema;
  
  private $expressions = array();
  private $types = array();
  
  
  public function __construct(Schema $schema) {
    $this->schema = $schema;
  }
  
  
  public function addAggregateColumn($name, $field_format, $type = NULL) {
    $alias = NULL;
    if (is_array($field_format)) {
      $alias = current($field_format);
      $field_format = key($field_format);
    }
    
    list($table_name, $column_name) = Query::convertFieldFormat($field_format);
    
    $raw = Query::raw(sprintf("%s(`%s`.`%s`)", $name, $table_name, $column_name));
    
    if ($type === NULL) {
      $type = $this->schema->getTable($table_name)->getColumn($column_name)->getType();
    }
    
    $this->addColumn(
      ($alias === NULL) ? $raw : array($raw => $alias),
      $type
    );
  }
  
  
  public function addColumn($field_format, $type = NULL) {
    $alias = NULL;
    
    if (is_array($field_format)) {
      $alias = current($field_format);
      $field_format = key($field_format);
    }
    
    $raw = Query::unpackRaw($field_format);
    if ($raw !== false) {
      $this->addRawColumn($raw, $alias, $type);
    }
    else if (preg_match(Query::FIELD_FORMAT_REGEX, $field_format, $regex_matches)) {
      list(, $table_name, $column_name) = $regex_matches;
      $this->addRegularColumn($table_name, $column_name, $alias, $type);
    }
    else {
      throw new \InvalidArgumentException(sprintf("Got unknown field format: %s", $field_format));
    }
  }
  
  
  private function addRawColumn($raw, $alias, $type = NULL) {
    $this->addExpression($raw, $alias);
    
    if ($type === NULL) {
      throw new \InvalidArgumentException(sprintf("Expected type to be explicitly defined for raw expression: %s", $raw));
    }
    
    $this->setType(
      ($alias === NULL) ? $raw : $alias,
      $type
    );
  }
  
  
  private function addRegularColumn($table_name, $column_name, $alias = NULL, $type = NULL) {
    if ($column_name == '*') {
      if ($alias !== NULL) {
        throw new \InvalidArgumentException(sprintf("Got alias for wildcard expression: %s.*", $table_name));
      }
      
      $this->addExpression(sprintf("`%s`.*", $table_name));
      $this->setAllTypesForTable($table_name);
    }
    else {
      $this->addExpression(sprintf("`%s`.`%s`", $table_name, $column_name), $alias);
      
      if ($type === NULL) {
        $type = $this->schema->getTable($table_name)->getColumn($column_name)->getType();
      }
      
      $this->setType(
        ($alias === NULL) ? $column_name : $alias,
        $type
      );
    }
  }
  
  
  private function addExpression($expression, $alias = NULL) {
    $this->expressions[] = ($alias === NULL) ? $expression : sprintf("%s AS `%s`", $expression, $alias);
  }
  
  
  private function setType($alias, $type) {
    if (is_string($type)) {
      $type = Type::getType($type);
    }
    $this->types[$alias] = $type;
  }
  
  
  private function setAllTypesForTable($table_name) {
    $columns = $this->schema->getTable($table_name)->getColumns();
    foreach ($columns as $column) {
      $this->setType($column->getName(), $column->getType());
    }
  }
  
  
  public function clearColumns() {
    $this->expressions = array();
    $this->types = array();
  }
  
  
  public function getTypes() {
    return $this->types;
  }
  
  
  public function isEmpty() {
    return (count($this->expressions) == 0);
  }
  
  
  public function toSQL() {
    return "SELECT " . implode(', ', $this->expressions);
  }
}
