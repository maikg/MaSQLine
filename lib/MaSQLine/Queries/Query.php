<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

abstract class Query {
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
  
  
  public static function convertFieldFormat($field_format, $default_table_name = NULL) {
    try {
      if (!preg_match(Query::FIELD_FORMAT_REGEX, $field_format, $regex_matches)) {
        throw new \InvalidArgumentException(sprintf("Got invalid field format: %s", $field_format));
      }
    }
    catch (\InvalidArgumentException $e) {
      if ($default_table_name === NULL ||
          !preg_match(Query::FIELD_FORMAT_REGEX, sprintf("`%s`.`%s`", $default_table_name, $field_format), $regex_matches)) {
        throw $e;
      }
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
  
  
  public function __construct(Connection $conn, Schema $schema) {
    $this->conn = $conn;
    $this->schema = $schema;
  }
  
  
  public function __toString() {
    return $this->toSQL();
  }
  
  
  abstract public function toSQL();
  
  
  abstract public function getParamValues();
  
  
  abstract public function getParamTypes();
}
