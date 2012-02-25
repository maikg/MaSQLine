<?PHP
namespace MaSQLine\Queries\Clauses;

abstract class Clause {
  abstract public function toSQL();
  abstract public function isEmpty();
  
  public function getParamValues() {
    return array();
  }
  
  public function getParamTypes() {
    return array();
  }
}
