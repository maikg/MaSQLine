<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Types\Type;

class AggregateColumnExpression extends ColumnExpression {
  private $query;
  private $func_name;
  private $col_expr;
  private $type;
  
  
  public function __construct(Query $query, $func_name, ColumnExpression $col_expr, $type = NULL) {
    $this->query = $query;
    $this->func_name = $func_name;
    $this->col_expr = $col_expr;
    $this->type = ColumnExpression::convertType($type);
  }
  
  
  public function getType() {
    if ($this->type !== NULL) {
      return $this->type;
    }
    
    return $this->col_expr->getType();
  }
  
  
  public function toString() {
    return sprintf('%s(%s)', $this->func_name, $this->col_expr->toString());
  }
  
  
  public function getDefaultAlias() {
    return $this->toString();
  }
}
