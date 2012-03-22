<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class SelectQuery extends FetchQuery {
  private $clauses_manager;
  
  
  public function __construct(Connection $connection, Schema $schema) {
    parent::__construct($connection, $schema);
    
    $this->clauses_manager = new ClausesManager($this->createClauses());
  }
  
  
  private function createClauses() {
    return array(
      'SELECT'      => new Clauses\SelectClause($this),
      'FROM'        => new Clauses\FromClause($this),
      'WHERE'       => new Clauses\ConditionsClause($this, 'WHERE'),
      'GROUP BY'    => new Clauses\GroupByClause($this),
      'HAVING'      => new Clauses\ConditionsClause($this, 'HAVING'),
      'ORDER BY'    => new Clauses\OrderByClause($this),
      'LIMIT'       => new Clauses\LimitClause($this)
    );
  }
  
  
  private function getClause($clause_name) {
    return $this->clauses_manager->getClause($clause_name);
  }
  
  
  public function getFormat() {
    return $this->clauses_manager->getFormat();
  }
  
  
  public function getValues() {
    return $this->clauses_manager->getValues();
  }
  
  
  public function getTypes() {
    return $this->clauses_manager->getTypes();
  }
  
  
  private function expandColumnExpression($col_expr) {
    if (is_array($col_expr)) {
      $column = key($col_expr);
      $alias = current($col_expr);
    }
    else {
      $column = $col_expr;
      $alias = NULL;
    }
    
    return array($column, $alias);
  }
  
  
  public function select() {
    $args = func_get_args();
    
    foreach ($args as $arg) {
      list($column, $alias) = $this->expandColumnExpression($arg);
      $this->getClause('SELECT')->addColumn($column, $alias);
    }
    
    return $this;
  }
  
  
  public function selectColumn($col_expr, $type = NULL) {
    list($column, $alias) = $this->expandColumnExpression($col_expr);
    $this->getClause('SELECT')->addColumn($column, $alias, $type);
    return $this;
  }
  
  
  public function selectAggr($name, $col_expr, $alias = NULL, $type = NULL) {
    $this->getClause('SELECT')->addAggregateColumn($name, $col_expr, $alias, $type);
    return $this;
  }
  
  
  public function selectCount($col_expr = NULL, $alias = NULL) {
    if ($col_expr === NULL) {
      $col_expr = Expression::raw('*');
    }
    $this->getClause('SELECT')->addAggregateColumn('COUNT', $col_expr, $alias, 'integer');
    
    return $this;
  }
  
  
  public function from($table_name) {
    $this->getClause('FROM')->setTableName($table_name);
    return $this;
  }
  
  
  private function processJoinArgs($a, $b) {
    if ($b instanceof Expression) {
      $target_table_name = $a;
      $expr = $b;
    }
    else {
      $target_table_name = ColumnPath::parse($this, $b)->getTable()->getName();
      $expr = $this->expr()->eqCol($a, $b);
    }
    
    return array($target_table_name, $expr);
  }
  
  
  public function innerJoin($a, $b) {
    list($target_table_name, $expr) = $this->processJoinArgs($a, $b);
    $this->getClause('FROM')->addInnerJoin($target_table_name, $expr);
    return $this;
  }
  
  
  public function leftJoin($a, $b) {
    list($target_table_name, $expr) = $this->processJoinArgs($a, $b);
    $this->getClause('FROM')->addLeftJoin($target_table_name, $expr);
    return $this;
  }
  
  
  public function where(Expression $expr) {
    $this->getClause('WHERE')->setConditionsExpression($expr);
    return $this;
  }
  
  
  public function limit($limit) {
    $this->getClause('LIMIT')->setLimit($limit);
    return $this;
  }
  
  
  public function offset($offset) {
    $this->getClause('LIMIT')->setOffset($offset);
    return $this;
  }
  
  
  public function orderBy() {
    $args = func_get_args();
    
    foreach ($args as $arg) {
      $sort_dir = NULL;
      if ($arg{0} == '+') {
        $sort_dir = Clauses\OrderByClause::SORT_ASC;
        $arg = substr($arg, 1);
      }
      else if ($arg{0} == '-') {
        $sort_dir = Clauses\OrderByClause::SORT_DESC;
        $arg = substr($arg, 1);
      }
      
      $this->getClause('ORDER BY')->addColumn($arg, $sort_dir);
    }
    
    return $this;
  }
  
  
  public function groupBy() {
    $args = func_get_args();
    
    foreach ($args as $arg) {
      $this->getClause('GROUP BY')->addColumn($arg);
    }
    
    return $this;
  }
  
  
  public function having(Expression $expr) {
    $this->getClause('HAVING')->setConditionsExpression($expr);
    return $this;
  }
  
  
  public function getConversionTypes() {
    return $this->getClause('SELECT')->getConversionTypes();
  }
}
