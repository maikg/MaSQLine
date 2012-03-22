<?PHP
namespace MaSQLine\Queries;

class ExpressionBuilder {
  private $query;
  
  
  public function __construct(Query $query) {
    $this->query = $query;
  }
  
  
  public function getQuery() {
    return $this->query;
  }
  
  
  public function raw($expr) {
    return Expression::raw($expr);
  }
  
  
  private function parseColumnExpression($column_expr, $type = NULL) {
    return ColumnExpression::parse($this->query, $column_expr, $type);
  }
  
  
  private function simpleCondition($column_expr, $format, $value = NULL, $type = NULL) {
    $final_format = NULL;
    
    if (!($column_expr instanceof ColumnExpression)) {
      $column_expr = $this->parseColumnExpression($column_expr, $type);
    }
    
    $final_format = sprintf($format, $column_expr->toString());
    
    if ($value === NULL) {
      return new Expression($final_format);
    }
        
    return new Expression(
      $final_format,
      array($value),
      array($column_expr->getType())
    );
  }
  
  
  public function not(Expression $expr) {
    return new Expression(
      sprintf('NOT (%s)', $expr->getFormat()),
      $expr->getValues(),
      $expr->getTypes()
    );
  }
  
  
  public function equalTo($column_expr, $value, $type = NULL) {
    return $this->simpleCondition($column_expr, '%s = ?', $value, $type);
  }
  public function eq($column_expr, $value, $type = NULL) {
    return $this->equalTo($column_expr, $value, $type);
  }
  
  
  public function greaterThan($column_expr, $value, $type = NULL) {
    return $this->simpleCondition($column_expr, '%s > ?', $value, $type);
  }
  public function gt($column_expr, $value, $type = NULL) {
    return $this->greaterThan($column_expr, $value, $type);
  }
  
  
  public function greaterThanOrEqualTo($column_expr, $value, $type = NULL) {
    return $this->simpleCondition($column_expr, '%s >= ?', $value, $type);
  }
  public function gte($column_expr, $value, $type = NULL) {
    return $this->greaterThanOrEqualTo($column_expr, $value, $type);
  }
  
  
  public function lessThan($column_expr, $value, $type = NULL) {
    return $this->simpleCondition($column_expr, '%s < ?', $value, $type);
  }
  public function lt($column_expr, $value, $type = NULL) {
    return $this->lessThan($column_expr, $value, $type);
  }
  
  
  public function lessThanOrEqualTo($column_expr, $value, $type = NULL) {
    return $this->simpleCondition($column_expr, '%s <= ?', $value, $type);
  }
  public function lte($column_expr, $value, $type = NULL) {
    return $this->lessThanOrEqualTo($column_expr, $value, $type);
  }
  
  
  public function in($column_expr, array $values, $type = NULL) {
    $column_expr = $this->parseColumnExpression($column_expr);
    
    $placeholders = array_fill(0, count($values), '?');
    $format = sprintf('%s IN (%s)', $column_expr->toString(), implode(',', $placeholders));
    
    if ($type === NULL) {
      $type = $column_expr->getType();
    }
    $types = array_fill(0, count($values), $type);
    
    return new Expression($format, $values, $types);
  }
  
  
  public function like($column_expr, $value, $type = NULL) {
    return $this->simpleCondition($column_expr, '%s LIKE ?', $value, $type);
  }
  
  
  public function isNull($column_expr) {
    return $this->simpleCondition($column_expr, '%s IS NULL');
  }
  public function null($column_expr) {
    return $this->isNull($column_expr);
  }
  
  
  private function simpleColumnPairCondition($column_expr, $other_column_expr, $format) {
    $column_expr = $this->parseColumnExpression($column_expr);
    $other_column_expr = $this->parseColumnExpression($other_column_expr);
    $format = sprintf($format, $column_expr->toString(), $other_column_expr->toString());
    return new Expression($format);
  }
  
  
  public function equalToColumn($column_expr, $other_column_expr) {
    return $this->simpleColumnPairCondition($column_expr, $other_column_expr, '%s = %s');
  }
  public function eqCol($column_expr, $other_column_expr) {
    return $this->equalToColumn($column_expr, $other_column_expr);
  }
  
  
  public function greaterThanColumn($column_expr, $other_column_expr) {
    return $this->simpleColumnPairCondition($column_expr, $other_column_expr, '%s > %s');
  }
  public function gtCol($column_expr, $other_column_expr) {
    return $this->greaterThanColumn($column_expr, $other_column_expr);
  }
  
  
  public function greaterThanOrEqualToColumn($column_expr, $other_column_expr) {
    return $this->simpleColumnPairCondition($column_expr, $other_column_expr, '%s >= %s');
  }
  public function gteCol($column_expr, $other_column_expr) {
    return $this->greaterThanOrEqualToColumn($column_expr, $other_column_expr);
  }
  
  
  public function lessThanColumn($column_expr, $other_column_expr) {
    return $this->simpleColumnPairCondition($column_expr, $other_column_expr, '%s < %s');
  }
  public function ltCol($column_expr, $other_column_expr) {
    return $this->lessThanColumn($column_expr, $other_column_expr);
  }
  
  
  public function lessThanOrEqualToColumn($column_expr, $other_column_expr) {
    return $this->simpleColumnPairCondition($column_expr, $other_column_expr, '%s <= %s');
  }
  public function lteCol($column_expr, $other_column_expr) {
    return $this->lessThanOrEqualToColumn($column_expr, $other_column_expr);
  }
  
  
  private function compoundCondition(array $expressions, $glue) {
    return new CompoundExpression($glue, $expressions);
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
