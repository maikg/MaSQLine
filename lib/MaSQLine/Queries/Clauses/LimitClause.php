<?PHP
namespace MaSQLine\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use MaSQLine\Queries\Query;

class LimitClause extends Clause {
  private $limit;
  private $offset;
  
  
  public function setLimit($limit) {
    $this->limit = $limit;
  }
  
  
  public function setOffset($offset) {
    $this->offset = $offset;
  }
  
  
  public function isEmpty() {
    return ($this->limit === NULL && $this->offset === NULL);
  }
  
  
  public function toSQL() {
    $limit = ($this->limit === NULL) ? PHP_INT_MAX : $this->limit;
    $content = ($this->offset === NULL) ? $limit : sprintf("%d,%d", $this->offset, $limit);
    return sprintf("LIMIT %s", $content);
  }
}
