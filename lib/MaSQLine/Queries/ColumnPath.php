<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Schema\Schema;

class ColumnPath extends ColumnExpression {
  public static function parse(Schema $schema, $expr, $type = NULL) {
    if ($type !== NULL) {
      $type = ColumnExpression::convertType($type);
    }
    
    list($table_name, $column_name) = explode('.', $expr);
    return new ColumnPath($schema, $table_name, $column_name, $type);
  }
  
  
  private $table;
  private $column;
  
  private $type;
  
  
  public function __construct(Schema $schema, $table_name, $column_name, Type $type = NULL) {
    $this->table = $schema->getTable($table_name);
    $this->column = $this->table->getColumn($column_name);
    
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
}
