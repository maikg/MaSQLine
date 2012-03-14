<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Schema\Schema;

class ColumnPath {
  public static function parse(Schema $schema, $path) {
    list($table_name, $column_name) = explode('.', $path);
    return new ColumnPath($schema, $table_name, $column_name);
  }
  
  private $table;
  private $column;
  
  
  public function __construct(Schema $schema, $table_name, $column_name) {
    $this->table = $schema->getTable($table_name);
    $this->column = $this->table->getColumn($column_name);
  }
  
  
  public function getType() {
    return $this->column->getType();
  }
  
  
  public function getTable() {
    return $this->table;
  }
  
  
  public function getColumn() {
    return $this->column;
  }
  
  
  public function toQuotedString() {
    return sprintf('`%s`.`%s`', $this->table->getName(), $this->column->getName());
  }
}
