<?PHP
namespace MaSQLine\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use MaSQLine\Queries\Query;
use MaSQLine\Queries\ColumnExpression;

class GroupByClause extends Clause {
  private $schema;
  private $columns = array();
  
  
  public function __construct(Schema $schema) {
    $this->schema = $schema;
  }
  
  
  public function addColumn($field_format) {
    $this->columns[] = ColumnExpression::parse($this->schema, $field_format);
  }
    
  
  public function isEmpty() {
    return (count($this->columns) == 0);
  }
  
  
  public function toSQL() {
    return sprintf("GROUP BY %s", implode(', ', $this->columns));
  }
}
