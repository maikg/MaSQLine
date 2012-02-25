<?PHP
namespace MaSQLine\Queries\Clauses;

use Doctrine\DBAL\Schema\Schema;
use MaSQLine\Queries\Query;

class OrderByClause extends Clause {
  const SORT_ASC = '+';
  const SORT_DESC = '-';
  
  
  private $expressions = array();
  
  
  public function addColumn($field_format, $direction = NULL) {
    if ($direction === NULL) {
      $direction = self::SORT_ASC;
    }
    
    $this->expressions[] = sprintf("%s %s", Query::quoteFieldFormat($field_format), $this->convertDirection($direction));
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
  
  
  public function isEmpty() {
    return (count($this->expressions) == 0);
  }
  
  
  public function toSQL() {
    return sprintf("ORDER BY %s", implode(', ', $this->expressions));
  }
}
