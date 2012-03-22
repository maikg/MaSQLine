<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Query;
use MaSQLine\Queries\Expression;
use MaSQLine\Queries\ColumnExpression;

class GroupByClause extends Expression {
  private $query;
  private $columns = array();
  
  
  public function __construct(Query $query) {
    $this->query = $query;
  }
  
  
  public function addColumn($col_expr) {
    $this->columns[] = ColumnExpression::parse($this->query, $col_expr);
  }
  
  
  public function getFormat() {
    if (count($this->columns) == 0) {
      return '';
    }
    
    return sprintf("GROUP BY %s", implode(', ', $this->columns));
  }
}
