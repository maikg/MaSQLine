<?PHP
namespace MaSQLine\Queries;

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
  
  
  public static function convertFieldFormat($field_format) {
    if (!preg_match(Query::FIELD_FORMAT_REGEX, $field_format, $regex_matches)) {
      throw new \InvalidArgumentException(sprintf("Got invalid field format: %s", $field_format));
    }
    
    list(, $table_name, $column_name) = $regex_matches;
    
    return array($table_name, $column_name);
  }
  
  
  public static function quoteFieldFormat($field_format) {
    $parts = explode('.', $field_format);
    $parts = array_map(function($part) {
      return sprintf("`%s`", $part);
    }, $parts);
    return implode('.', $parts);
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
