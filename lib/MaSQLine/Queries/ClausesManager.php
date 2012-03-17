<?PHP
namespace MaSQLine\Queries;

/**
 * Manages a set of clauses. This class is useful if a query can be built exclusively with a
 * set of clauses that always maintain the same order relative to each other.
 *
 * @author Maik Gosenshuis
 */
class ClausesManager {
  private $clauses;
  
  
  public function __construct(array $clauses) {
    $this->clauses = $clauses;
  }
  
  
  public function getClause($name) {
    return $this->clauses[$name];
  }
  
  
  public function getFormat() {
    $output = array();
    foreach ($this->clauses as $clause_name => $clause) {
      if ($clause === NULL || $clause->isEmpty()) {
        continue;
      }
      
      $output[] = $clause->getFormat();
    }
    
    return implode("\n", $output);
  }
  
  
  public function __toString() {
    return $this->getFormat();
  }
  
  
  public function getValues() {
    $values = array();
    foreach ($this->clauses as $clause) {
      $values = array_merge($values, $clause->getValues());
    }
    return $values;
  }
  
  
  public function getTypes() {
    $types = array();
    foreach ($this->clauses as $clause) {
      $types = array_merge($types, $clause->getTypes());
    }
    return $types;
  }
}
