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
    $this->where_clause = new Clauses\ConditionsClause($this, 'WHERE');
  }
  
  
  public function where(Expression $expr) {
    $this->where_clause->setConditionsExpression($expr);
        
    return $this;
  }
  
  
  public function getFormat() {
    if ($this->where_clause->isEmpty()) {
      return sprintf("DELETE FROM `%s`", $this->table_name);
    }
    
    return sprintf("DELETE FROM `%s` %s", $this->table_name, $this->where_clause->getFormat());
  }
  
  
  public function getValues() {
    return $this->where_clause->getValues();
  }
  
  
  public function getTypes() {
    return $this->where_clause->getTypes();
  }
}
