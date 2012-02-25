<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class SelectQuery extends ClausesQuery {
  protected function getClauses() {
    return array(
      'SELECT'      => new Clauses\SelectClause($this->schema),
      'FROM'        => new Clauses\FromClause($this->schema),
      'WHERE'       => new Clauses\ConditionsClause($this->schema, 'AND', 'WHERE'),
      'GROUP BY'    => new Clauses\GroupByClause(),
      'HAVING'      => new Clauses\ConditionsClause($this->schema, 'AND', 'HAVING'),
      'ORDER BY'    => new Clauses\OrderByClause(),
      'LIMIT'       => new Clauses\LimitClause()
    );
  }
  
  
  public function select() {
    $args = func_get_args();
    
    foreach ($args as $arg) {
      $this->getClause('SELECT')->addColumn($arg);
    }
    
    return $this;
  }
  
  
  public function selectColumn($field_format, $type = NULL) {
    $this->getClause('SELECT')->addColumn($field_format, $type);
    return $this;
  }
  
  
  public function selectAggr($name, $field_format, $alias = NULL, $type = NULL) {
    $this->getClause('SELECT')->addAggregateColumn($name, $field_format, $alias, $type);
    return $this;
  }
  
  
  public function selectCount($field_format = NULL, $alias = NULL) {
    if ($field_format === NULL) {
      $field_format = Query::raw('*');
    }
    $this->getClause('SELECT')->addAggregateColumn('COUNT', $field_format, $alias, Type::getType('integer'));
    
    return $this;
  }
  
  
  public function from($table_name) {
    $this->getClause('FROM')->setTableName($table_name);
    return $this;
  }
  
  
  public function innerJoin($origin, $target) {
    $this->getClause('FROM')->addInnerJoin($origin, $target);
    return $this;
  }
  
  
  public function leftJoin($origin, $target) {
    $this->getClause('FROM')->addLeftJoin($origin, $target);
    return $this;
  }
  
  
  public function where(\Closure $setup_where) {
    $setup_where($this->getClause('WHERE'));
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
  
  
  public function having(\Closure $setup_having) {
    $setup_having($this->getClause('HAVING'));
    return $this;
  }
  
  
  public function getConversionTypes() {
    return $this->getClause('SELECT')->getTypes();
  }
}
