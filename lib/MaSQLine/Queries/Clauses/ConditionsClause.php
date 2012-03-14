<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Expression;

class ConditionsClause extends Clause {
  private $prefix;
  private $expr;
  
  
  public function __construct($prefix) {
    $this->prefix = $prefix;
  }
  
  
  public function setExpression(Expression $expr) {
    $this->expr = $expr;
  }
  
  
  public function getParamValues() {
    return ($this->expr === NULL) ? array() : $this->expr->getValues();
  }
  
  
  public function getParamTypes() {
    return ($this->expr === NULL) ? array() : $this->expr->getTypes();
  }
  
  
  public function isEmpty() {
    return ($this->expr === NULL);
  }
  
  
  public function toSQL() {
    return sprintf('%s %s', $this->prefix, $this->expr->getFormat());
  }
}
