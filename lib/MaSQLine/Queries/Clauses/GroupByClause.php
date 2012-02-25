<?PHP
namespace MaSQLine\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use MaSQLine\Queries\Query;

class GroupByClause extends Clause {
  private $columns = array();
  
  
  public function addColumn($field_format) {
    $this->columns[] = Query::quoteFieldFormat($field_format);
  }
    
  
  public function isEmpty() {
    return (count($this->columns) == 0);
  }
  
  
  public function toSQL() {
    return sprintf("GROUP BY %s", implode(', ', $this->columns));
  }
}
