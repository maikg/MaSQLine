<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

abstract class ColumnExpression {
  public static function parse(Schema $schema, $expr, $type = NULL) {
    $raw = RawColumnExpression::parse($schema, $expr, $type);
    
    if ($raw !== NULL) {
      return $raw;
    }
    
    return ColumnPath::parse($schema, $expr, $type);
  }
  
  
  public static function convertType($type) {
    if (!($type instanceof Type)) {
      $type = Type::getType($type);
    }
    
    return $type;
  }
  
  
  abstract public function getType();
  abstract public function toString();
  
  
  public function __toString() {
    return $this->toString();
  }
}
