<?PHP
namespace MaSQLine\Queries;

use Doctrine\DBAL\Types\Type;

class RawColumnExpression extends ColumnExpression {
  private $raw;
  private $type;
  
  
  public function __construct($raw, Type $type) {
    $this->raw = $raw;
    $this->type = $type;
  }
  
  
  public function getType() {
    return $this->type;
  }
  
  
  public function toString() {
    return $this->raw;
  }
}
