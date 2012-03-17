<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

abstract class Query extends Expression {  
  protected $conn;
  protected $schema;
  
  
  public function __construct(Connection $conn, Schema $schema) {
    $this->conn = $conn;
    $this->schema = $schema;
  }
  
  
  public function __toString() {
    return $this->getFormat();
  }
  
  
  public function toSQL() {
    return $this->getFormat();
  }
}
