<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class ColumnPath extends ColumnExpression {
  public static function parse(Schema $schema, $expr, $type = NULL) {
    if (strpos($expr, '.') === false) {
      return NULL;
    }
    
    if ($type !== NULL) {
      $type = ColumnExpression::convertType($type);
    }
    
    list($table_name, $column_name) = explode('.', $expr);
    
    if ($column_name == '*') {
      // Expand wildcards.
      $table = $schema->getTable($table_name);
      $columns = array_values($table->getColumns());
      return array_map(function(Column $column) use ($table) {
        return new ColumnPath($table, $column);
      }, $columns);
    }
    
    $table = $schema->getTable($table_name);
    $column = $table->getColumn($column_name);
    return new ColumnPath($table, $column, $type);
  }
  
  
  private $table;
  private $column;
  
  private $type;
  
  
  public function __construct(Table $table, Column $column, Type $type = NULL) {
    $this->table = $table;
    $this->column = $column;
    
    $this->type = ($type === NULL) ? $this->column->getType() : $type;
  }
  
  
  public function getType() {
    return $this->type;
  }
  
  
  public function getTable() {
    return $this->table;
  }
  
  
  public function getColumn() {
    return $this->column;
  }
  
  
  public function toString() {
    return sprintf('`%s`.`%s`', $this->table->getName(), $this->column->getName());
  }
  
  
  public function toColumnString() {
    return sprintf('`%s`', $this->column->getName());
  }
  
  
  public function getDefaultAlias() {
    return $this->column->getName();
  }
}
