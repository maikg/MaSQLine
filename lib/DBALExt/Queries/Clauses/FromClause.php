<?PHP
namespace DBALExt\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;

class FromClause implements Clause {
  private $table_name;
  
  
  public function setTableName($table_name) {
    $this->table_name = $table_name;
  }
  
  
  public function isEmpty() {
    return ($this->table_name === NULL);
  }
  
  
  public function toSQL() {
    if (empty($this->table_name)) {
      throw new \RuntimeException("Expected a table name to be set.");
    }
    
    return sprintf("FROM `%s`", $this->table_name);
  }
}
