<?PHP
namespace DBALExt\Queries\Clauses;

interface Clause {
  public function toSQL();
  
  
  public function isEmpty();
}
