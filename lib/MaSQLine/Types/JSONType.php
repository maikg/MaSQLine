<?PHP
namespace MaSQLine\Types;

use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class JSONType extends TextType {
  const JSON = 'json';
  
  
  public function convertToPHPValue($value, AbstractPlatform $platform) {
    $json_string = parent::convertToPHPValue($value, $platform);
    
    if ($json_string === NULL) {
      return NULL;
    }
    
    return json_decode($json_string, true);
  }
  
  
  public function convertToDatabaseValue($value, AbstractPlatform $platform) {
    if ($value === NULL) {
      return NULL;
    }
    
    $value = json_encode($value);
    
    return parent::convertToDatabaseValue($value, $platform);
  }
  
  
  public function getName() {
    return self::JSON;
  }
}
