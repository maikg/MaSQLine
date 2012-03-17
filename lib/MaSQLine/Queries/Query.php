<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

abstract class Query {  
  protected $conn;
  protected $schema;
  
  
  public function __construct(Connection $conn, Schema $schema) {
    $this->conn = $conn;
    $this->schema = $schema;
  }
  
  
  public function __toString() {
    return $this->toSQL();
  }
  
  
  abstract public function toSQL();
  
  
  abstract public function getParamValues();
  
  
  abstract public function getParamTypes();
}
