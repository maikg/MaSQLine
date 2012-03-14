<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class ConditionsBuilder {
  private $schema;
  
  
  public function __construct(Schema $schema) {
    $this->schema = $schema;
  }
  
  
  private function parseColumnPath($column_path) {
    return ColumnPath::parse($this->schema, $column_path);
  }
  
  
  private function simpleCondition($column_path, $format, $value = NULL, $type = NULL) {
    if (!($column_path instanceof ColumnPath)) {
      $column_path = $this->parseColumnPath($column_path);
    }
    
    $final_format = sprintf($format, $column_path->toQuotedString());
    
    if ($value === NULL) {
      return new Expression($final_format);
    }
        
    return new Expression(
      $final_format,
      array($value),
      array(($type === NULL) ? $column_path->getType() : $type)
    );
  }
  
  
  public function not(Expression $expr) {
    return new Expression(
      sprintf('NOT (%s)', $expr->getFormat()),
      $expr->getValues(),
      $expr->getTypes()
    );
  }
  
  
  public function equalTo($column_path, $value) {
    return $this->simpleCondition($column_path, '%s = ?', $value);
  }
  public function eq($column_path, $value) {
    return $this->equalTo($column_path, $value);
  }
  
  
  public function greaterThan($column_path, $value) {
    return $this->simpleCondition($column_path, '%s > ?', $value);
  }
  public function gt($column_path, $value) {
    return $this->greaterThan($column_path, $value);
  }
  
  
  public function greaterThanOrEqualTo($column_path, $value) {
    return $this->simpleCondition($column_path, '%s >= ?', $value);
  }
  public function gte($column_path, $value) {
    return $this->greaterThanOrEqualTo($column_path, $value);
  }
  
  
  public function lessThan($column_path, $value) {
    return $this->simpleCondition($column_path, '%s < ?', $value);
  }
  public function lt($column_path, $value) {
    return $this->lessThan($column_path, $value);
  }
  
  
  public function lessThanOrEqualTo($column_path, $value) {
    return $this->simpleCondition($column_path, '%s <= ?', $value);
  }
  public function lte($column_path, $value) {
    return $this->lessThanOrEqualTo($column_path, $value);
  }
  
  
  public function in($column_path, array $values) {
    $column_path = $this->parseColumnPath($column_path);
    
    $placeholders = array_fill(0, count($values), '?');
    $format = sprintf('%s IN (%s)', $column_path->toQuotedString(), implode(',', $placeholders));
    $types = array_fill(0, count($values), $column_path->getType());
    
    return new Expression($format, $values, $types);
  }
  
  
  public function like($column_path, $value) {
    return $this->simpleCondition($column_path, '%s LIKE ?', $value);
  }
  
  
  public function isNull($column_path) {
    return $this->simpleCondition($column_path, '%s IS NULL');
  }
  public function null($column_path) {
    return $this->isNull($column_path);
  }
  
  
  public function equalToColumn($column_path, $other_column_path) {
    $column_path = $this->parseColumnPath($column_path);
    $other_column_path = $this->parseColumnPath($other_column_path);
    $format = sprintf('%s = %s', $column_path->toQuotedString(), $other_column_path->toQuotedString());
  }
  public function eqCol($column_path, $other_column_path) {
    return $this->equalToColumn($column_path, $other_column_path);
  }
  
  
  private function compoundCondition(array $expressions, $glue) {
    $formats = array_map(function(Expression $expr) {
      return $expr->getFormat();
    }, $expressions);
    $format = sprintf('(%s)', implode($glue, $formats));
    
    $values = array_reduce($expressions, function(array $result, Expression $expr) {
      return array_merge($result, $expr->getValues());
    }, array());
    
    $types = array_reduce($expressions, function(array $result, Expression $expr) {
      return array_merge($result, $expr->getTypes());
    }, array());
    
    return new Expression($format, $values, $types);
  }
  
  
  public function all() {
    $expressions = func_get_args();
    return $this->compoundCondition($expressions, ' AND ');
  }
  
  
  public function any() {
    $expressions = func_get_args();
    return $this->compoundCondition($expressions, ' OR ');
  }
}
