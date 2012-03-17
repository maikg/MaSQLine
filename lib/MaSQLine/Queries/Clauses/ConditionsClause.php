<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Expression;

class ConditionsClause extends Expression {
  private $prefix;
  private $expr;
  
  
  public function __construct($prefix) {
    $this->prefix = $prefix;
  }
  
  
  public function setConditionsExpression(Expression $expr) {
    $this->expr = $expr;
  }
  
  
  public function getValues() {
    return ($this->expr === NULL) ? array() : $this->expr->getValues();
  }
  
  
  public function getTypes() {
    return ($this->expr === NULL) ? array() : $this->expr->getTypes();
  }
  
  
  public function getFormat() {
    if ($this->expr === NULL) {
      return '';
    }
    
    return sprintf('%s %s', $this->prefix, $this->expr->getFormat());
  }
}
