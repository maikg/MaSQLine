<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Expression;
use MaSQLine\Queries\Query;
use MaSQLine\Queries\ColumnPath;

class OrderByClause extends Expression {
  const SORT_ASC = '+';
  const SORT_DESC = '-';
  
  
  private $query;
  private $expressions = array();
  
  
  public function __construct(Query $query) {
    $this->query = $query;
  }
  
  
  public function addColumn($col_expr, $direction = NULL) {    
    if ($direction === NULL) {
      $direction = self::SORT_ASC;
    }
    
    $col = ColumnPath::parse($this->query, $col_expr);
    $col_expr = ($col === NULL) ? sprintf('`%s`', $col_expr) : $col->toString();
    
    $this->expressions[] = sprintf("%s %s", $col_expr, $this->convertDirection($direction));
  }
  
  
  private function convertDirection($direction) {
    switch ($direction) {
      case self::SORT_ASC:
        return 'ASC';
        break;
      
      case self::SORT_DESC:
        return 'DESC';
        break;
      
      default:
        throw new \InvalidArgumentException(sprintf("Got invalid sort direction: %s", $direction));
    }
  }
  
  
  public function getFormat() {
    if (count($this->expressions) == 0) {
      return '';
    }
    
    return sprintf("ORDER BY %s", implode(', ', $this->expressions));
  }
}
