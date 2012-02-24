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
  
  
  private function addCondition($field_format, $operator, $value) {
    if (!preg_match(Query::FIELD_FORMAT_REGEX, $field_format, $regex_matches)) {
      throw new \InvalidArgumentException(sprintf("Got invalid field format: %s", $field_format));
    }
    
    list(, $table_name, $column_name) = $regex_matches;
    
    $this->conditions[] = sprintf("`%s`.`%s` %s ?", $table_name, $column_name, $operator);
    $this->params[] = $value;
    $this->addType($this->schema->getTable($table_name)->getColumn($column_name)->getType());
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
