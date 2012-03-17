<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class RawColumnExpression extends ColumnExpression {
  public static function parse($expr, $type) {
    $raw = Expression::unpackRaw($expr);
    
    if ($raw === false) {
      return NULL;
    }
    
    if ($type === NULL) {
      throw new \InvalidArgumentException(sprintf("Expected type to be set explicitly for raw expression: %s", $raw));
    }
    
    return new RawColumnExpression($raw, ColumnExpression::convertType($type));
  }
  
  
  private $raw;
  private $type;
  
  
  public function __construct($raw, Type $type) {
    $this->raw = $raw;
    $this->type = $type;
  }
  
  
  public function getType() {
    return $this->type;
  }
  
  
  public function toString() {
    return $this->raw;
  }
  
  
  public function getDefaultAlias() {
    return $this->raw;
  }
}
