<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class DeleteQuery extends ManipulationQuery {
  private $table_name;
  private $where_clause;
  
  
  public function __construct(Connection $conn, Schema $schema, $table_name) {
    parent::__construct($conn, $schema);
    
    $this->table_name = $table_name;
    $this->where_clause = new Clauses\ConditionsClause($this->schema, 'AND');
  }
  
  
  public function where(\Closure $setup_where) {
    $setup_where($this->where_clause);
    
    return $this;
  }
  
  
  public function toSQL() {
    if ($this->where_clause->isEmpty()) {
      return sprintf("DELETE FROM `%s`", $this->table_name);
    }
    
    return sprintf("DELETE FROM `%s` WHERE %s", $this->table_name, $this->where_clause->toSQL());
  }
  
  
  public function getParamValues() {
    return $this->where_clause->getParamValues();
  }
  
  
  public function getParamTypes() {
    return $this->where_clause->getParamTypes();
  }
}
