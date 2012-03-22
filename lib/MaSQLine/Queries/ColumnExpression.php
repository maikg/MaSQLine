<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Types\Type;

abstract class ColumnExpression {
  public static function parse(Query $query, $expr, $type = NULL) {
    $raw = RawColumnExpression::parse($expr, $type);
    
    if ($raw !== NULL) {
      return $raw;
    }
    
    return ColumnPath::parse($query, $expr, $type);
  }
  
  
  public static function convertType($type) {
    if (!($type instanceof Type)) {
      $type = Type::getType($type);
    }
    
    return $type;
  }
  
  
  abstract public function getType();
  abstract public function toString();
  abstract public function getDefaultAlias();
  
  
  public function __toString() {
    return $this->toString();
  }
}
