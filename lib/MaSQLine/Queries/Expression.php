<?PHP
namespace MaSQLine\Queries;

class Expression {
  const RAW_PACKED_REGEX = '/^\{\{\{(.+?)\}\}\}$/';
  
  
  public static function raw($string) {
    return sprintf('{{{%s}}}', $string);
  }
  
  
  public static function unpackRaw($string) {
    if (!preg_match(self::RAW_PACKED_REGEX, $string, $regex_matches)) {
      return false;
    }
    
    return $regex_matches[1];
  }
  
  
  private $format = '';
  private $values = array();
  private $types = array();
  
  
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
  
  
  public function isEmpty() {
    $format = $this->getFormat();
    return empty($format);
  }
}
