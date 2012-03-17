<?PHP
namespace MaSQLine\Queries\Clauses;

use MaSQLine\Queries\Query;
use MaSQLine\Queries\Expression;
use MaSQLine\Queries\ColumnExpression;
use MaSQLine\Queries\ColumnPath;
use MaSQLine\Queries\RawColumnExpression;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class SelectClause extends Clause {
  private $schema;
  
  private $columns = array();
  private $aliases = array();
  
  
  public function __construct(Schema $schema) {
    $this->schema = $schema;
  }
  
  
  public function addAggregateColumn($name, $col_expr, $alias = NULL, $type = NULL) {
    $col = ColumnExpression::parse($this->schema, $col_expr, $type);
    $field_expr = sprintf('%s(%s)', $name, $col->toString());
    
    if ($type === NULL) {
      if ($col instanceof ColumnPath) {
        $type = $col->getColumn()->getType();
      }
      else {
        throw new \InvalidArgumentException("Expected type to be specified for aggregate column.");
      }
    }
    
    $this->columns[] = new RawColumnExpression($field_expr, ColumnExpression::convertType($type));
    $this->aliases[] = $alias;
  }
  
  
  public function addColumn($expr, $alias = NULL, $type = NULL) {
    $col = ColumnExpression::parse($this->schema, $expr, $type);
    
    if (!is_array($col)) {
      $this->columns[] = $col;
      $this->aliases[] = $alias;
    }
    else {
      if ($alias !== NULL) {
        throw new \InvalidArgumentException("Can't specify an alias for wildcard column paths.");
      }
      if ($type !== NULL) {
        throw new \InvalidArgumentException("Can't specify a type for wildcard column paths.");
      }
      
      $this->columns = array_merge($this->columns, $col);
      $this->aliases = array_merge($this->aliases, array_fill(0, count($col), NULL));
    }
  }
  
  
  public function clearColumns() {
    $this->columns = array();
    $this->aliases = array();
  }
  
  
  public function getTypes() {
    $types = array();
    
    foreach ($this->columns as $i => $col) {
      $alias = $this->aliases[$i];
      if ($alias === NULL) {
        $alias = $col->getDefaultAlias();
      }
      
      $types[$alias] = $col->getType();
    }
    
    return $types;
  }
  
  
  public function isEmpty() {
    return (count($this->columns) == 0);
  }
  
  
  public function toSQL() {
    $expressions = array_map(function(ColumnExpression $col, $alias) {
      if ($alias !== NULL) {
        return sprintf('%s AS `%s`', $col->toString(), $alias);
      }
      
      return $col->toString();
    }, $this->columns, $this->aliases);
    
    return "SELECT " . implode(', ', $expressions);
  }
}
