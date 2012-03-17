<?PHP
namespace MaSQLine\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use MaSQLine\Queries\Query;
use MaSQLine\Queries\Expression;
use MaSQLine\Queries\ColumnExpression;

class GroupByClause extends Expression {
  private $schema;
  private $columns = array();
  
  
  public function __construct(Schema $schema) {
    $this->schema = $schema;
  }
  
  
  public function addColumn($col_expr) {
    $this->columns[] = ColumnExpression::parse($this->schema, $col_expr);
  }
  
  
  public function getFormat() {
    if (count($this->columns) == 0) {
      return '';
    }
    
    return sprintf("GROUP BY %s", implode(', ', $this->columns));
  }
}
