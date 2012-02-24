<?PHP
namespace DBALExt\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Types\Type;

class Query {
  const RAW_PACKED_REGEX = '/^\{\{\{(.+?)\}\}\}$/';
  const FIELD_FORMAT_REGEX = '/^([^.]+)\.([^.]+)$/';
  
  
  public static function raw($expression) {
    return sprintf("{{{%s}}}", $expression);
  }
  
  
  public static function unpackRaw($expression) {
    if (!preg_match(self::RAW_PACKED_REGEX, $expression, $regex_matches)) {
      return false;
    }
    
    return $regex_matches[1];
  }
  
  
  protected $conn;
  protected $schema;
  
  private $conversion_types = array();
  
  
  public function __construct(Connection $conn, Schema $schema) {
    $this->conn = $conn;
    $this->schema = $schema;
  }
  
  
  protected function setConversionType($column_alias, $type) {
    $this->conversion_types[$column_alias] = $type;
  }
}
