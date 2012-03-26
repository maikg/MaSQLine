<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Query;
use MaSQLine\Queries\Expression;
use MaSQLine\Queries\ColumnExpression;
use MaSQLine\Queries\ColumnPath;
use MaSQLine\Queries\RawColumnExpression;
use MaSQLine\Queries\AggregateColumnExpression;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Column;

class SelectClause extends Expression {
  private $query;
  
  private $columns = array();
  
  
  public function __construct(Query $query) {
    $this->query = $query;
  }
  
  
  public function addAggregateColumn($name, $col_expr, $alias = NULL, $type = NULL) {
    $inner_col = ColumnExpression::parse($this->query, $col_expr, $type);
    $col = new AggregateColumnExpression($this->query, $name, $inner_col, $type);
    $col->setAlias($alias);
    $this->columns[] = $col;
  }
  
  
  public function addColumn($expr, $alias = NULL, $type = NULL) {
    $col = ColumnExpression::parse($this->query, $expr, $type);
    
    if (($col instanceof ColumnPath) && $col->isWildcardPath() && $alias !== NULL) {
      throw new \InvalidArgumentException("Can't specify an alias for wildcard column paths.");
    }
    else {
      $col->setAlias($alias);
    }
    
    $this->columns[] = $col;
  }
  
  
  public function clearColumns() {
    $this->columns = array();
    $this->aliases = array();
  }
  
  
  public function getConversionTypes() {
    $types = array();
    
    foreach ($this->columns as $i => $col) {
      if (($col instanceof ColumnPath) && $col->isWildcardPath()) {
        $types = array_merge($types, $this->fetchTypesForWildcardColumnPath($col));
      }
      else {
        $alias = $col->getAlias();
        if ($alias === NULL) {
          $alias = $col->getDefaultAlias();
        }

        $types[$alias] = $col->getType();
      }
    }
    
    return $types;
  }
  
  
  private function fetchTypesForWildcardColumnPath(ColumnPath $col) {
    $table_name = $this->query->getRealTableName($col->getTableName());
    $table_cols = $this->query->getSchema()->getTable($table_name)->getColumns();
    return array_map(function(Column $col) {
      return $col->getType();
    }, $table_cols);
  }
  
  
  public function getFormat() {
    if (count($this->columns) == 0) {
      return '';
    }
    
    $expressions = array_map(function(ColumnExpression $col) {
      $alias = $col->getAlias();
      if ($alias !== NULL) {
        return sprintf('%s AS `%s`', $col->toString(), $alias);
      }
      
      return $col->toString();
    }, $this->columns);
    
    return "SELECT " . implode(', ', $expressions);
  }
}
