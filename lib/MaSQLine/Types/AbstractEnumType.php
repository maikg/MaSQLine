<?PHP
namespace MaSQLine\Types;

use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Abstract base class for ENUM types. Unfortunately, due to the way DBAL is set up, each
 * ENUM type requires a separate implementation. This abstract base class takes most of the
 * pain out of creating one.
 *
 * @package MaSQLine\Types
 */
abstract class AbstractEnumType extends SmallIntType {
  protected static $name;
  protected static $values;
  
  
  public static function getValues() {
    return static::$values;
  }
  
  
  public function convertToPHPValue($value, AbstractPlatform $platform) {
    $index = parent::convertToPHPValue($value, $platform);
    
    if ($index === NULL) {
      return NULL;
    }
    
    assert('isset(static::$values[$index])');
    if (!isset(static::$values[$index])) {
      return $index;
    }
    
    return static::$values[$index];
  }
  
  
  public function convertToDatabaseValue($value, AbstractPlatform $platform) {
    if ($value === NULL) {
      return NULL;
    }
    
    $index = array_search($value, static::$values);
    
    assert('$index !== false');
    if ($index === false) {
      return $value;
    }
    
    return $index;
  }
  
  
  public function getName() {
    return static::$name;
  }
}
