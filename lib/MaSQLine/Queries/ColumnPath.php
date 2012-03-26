<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class ColumnPath extends ColumnExpression {
  public static function parse(Query $query, $expr, $type = NULL) {
    if (strpos($expr, '.') === false) {
      return NULL;
    }
    
    if ($type !== NULL) {
      $type = ColumnExpression::convertType($type);
    }
    
    list($table_name, $column_name) = explode('.', $expr);
    
    return new ColumnPath($query, $table_name, $column_name, $type);
  }
  
  private $query;
  
  private $table_name;
  private $column_name;
  
  private $forced_type;
  
  
  public function __construct(Query $query, $table_name, $column_name, Type $forced_type = NULL) {
    $this->query = $query;
    
    $this->table_name = $table_name;
    $this->column_name = $column_name;
    
    $this->forced_type = $forced_type;
  }
  
  
  public function getType() {
    if ($this->forced_type !== NULL) {
      return $this->forced_type;
    }
    
    $table_name = $this->query->getRealTableName($this->table_name);
    
    return $this->query
      ->getSchema()
      ->getTable($table_name)
      ->getColumn($this->column_name)
      ->getType();
  }
  
  
  public function getTableName() {
    return $this->table_name;
  }
  
  
  public function getColumnName() {
    return $this->column_name;
  }
  
  
  public function isWildcardPath() {
    return ($this->column_name == '*');
  }
  
  
  public function toString() {    
    if ($this->isWildcardPath()) {
      return sprintf('`%s`.*', $this->table_name);
    }
    
    return sprintf('`%s`.`%s`',
      $this->table_name,
      $this->column_name
    );
  }
  
  
  public function toColumnString() {
    return sprintf('`%s`', $this->column_name);
  }
  
  
  public function getDefaultAlias() {
    return $this->column_name;
  }
}
