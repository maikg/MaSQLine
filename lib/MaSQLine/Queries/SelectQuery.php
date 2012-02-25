<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class SelectQuery extends ClausesQuery {
  protected function getClauses() {
    return array(
      'SELECT'      => new Clauses\SelectClause($this->schema),
      'FROM'        => new Clauses\FromClause($this->schema),
      'WHERE'       => new Clauses\ConditionsClause($this->schema, 'AND', 'WHERE'),
      // 'GROUP BY'    => NULL,
      // 'HAVING'      => NULL,
      'ORDER BY'    => new Clauses\OrderByClause(),
      'LIMIT'       => new Clauses\LimitClause()
    );
  }
  
  
  public function select() {
    $args = func_get_args();
    
    $this->getClause('SELECT')->clearColumns();
    foreach ($args as $arg) {
      $this->getClause('SELECT')->addColumn($arg);
    }
    
    return $this;
  }
  
  
  public function addSelectColumn($field_format, $type = NULL) {
    $select_clause = $this->getClause('SELECT');
    $select_clause->addColumn($field_format, $type);
    
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
  
  
  public function getConversionTypes() {
    return $this->getClause('SELECT')->getTypes();
  }
}
