<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

abstract class ColumnExpression {
  public static function parse(Schema $schema, $expr, $type = NULL) {
    if ($type !== NULL) {
      $type = self::convertType($type);
    }
    
    $raw = Expression::unpackRaw($expr);
    
    if ($raw !== false) {
      if ($type === NULL) {
        throw new \RuntimeException("Expected type to be set explicitly for raw expression: %s", $raw);
      }
      
      return new RawColumnExpression($raw, $type);
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
