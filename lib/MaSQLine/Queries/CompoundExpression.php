<?PHP
namespace MaSQLine\Queries;

class CompoundExpression extends Expression implements \ArrayAccess {
  private $format_glue;
  
  private $expressions = array();
  
  
  public function __construct($format_glue, array $expressions = array()) {
    $this->format_glue = $format_glue;
    $this->expressions = $expressions;
  }
  
  
  public function addExpression(Expression $expr) {
    $this->expressions[] = $expr;
  }
  
  
  public function getExpressions() {
    return $this->expressions;
  }
  
  
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->expressions);
  }
  
  
  public function offsetGet($offset) {
    return $this->expressions[$offset];
  }
  
  
  public function offsetSet($offset, $value) {
    $this->expressions[$offset] = $value;
  }
  
  
  public function offsetUnset($offset) {
    unset($this->expressions[$offset]);
  }
  
  
  public function getFormat() {
    $formats = array_map(function(Expression $expr) {
      return $expr->getFormat();
    }, $this->expressions);
    
    if (is_callable($this->format_glue)) {
      return call_user_func($this->format_glue, $formats);
    }
    else {
      return sprintf('(%s)', implode($this->format_glue, $formats));
    }
  }
  
  
  public function getValues() {
    return array_reduce($this->expressions, function(array $result, Expression $expr) {
      return array_merge($result, $expr->getValues());
    }, array());
  }
  
  
  public function getTypes() {
    return array_reduce($this->expressions, function(array $result, Expression $expr) {
      return array_merge($result, $expr->getTypes());
    }, array());
  }
}
