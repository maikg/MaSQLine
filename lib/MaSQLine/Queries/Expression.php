<?PHP
namespace MaSQLine\Queries;

class Expression {
  private $format;
  private $values;
  private $types;
  
  
  public function __construct($format, array $values = array(), array $types = array()) {
    $this->format = $format;
    $this->values = $values;
    $this->types = $types;
  }
  
  
  public function getFormat() {
    return $this->format;
  }
  
  
  public function getValues() {
    return $this->values;
  }
  
  
  public function getTypes() {
    return $this->types;
  }
}
