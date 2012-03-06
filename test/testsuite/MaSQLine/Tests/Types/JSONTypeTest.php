<?PHP
namespace MaSQLine\Tests\Types;

use MaSQLine\Types\JSONType;
use MaSQLine\Queries\SelectQuery;
use MaSQLine\Queries\InsertQuery;
use Doctrine\DBAL\Types\Type;

class JSONTypeTest extends \MaSQLine\Tests\TestCase {
  public static function setUpBeforeClass() {
    Type::addType('json', 'MaSQLine\Types\JSONType');
  }
  
  
  public function setUp() {
    $this->setUpWithSchemaFixture('json_schema');
  }
  
  
  public function testSaveJSON() {
    $data = array(
      'nested' => array(
        'data' => true
      )
    );
    
    $query = new InsertQuery($this->conn, $this->schema, 'anything');
    $query->setValues(array('data' => $data))->execute();
    
    $query = new SelectQuery($this->conn, $this->schema);
    $unconverted_data = $query
      ->selectColumn('anything.data', 'text')
      ->from('anything')
      ->fetchValue();
    
    $this->assertEquals(json_encode($data), $unconverted_data);
    
    $query = new SelectQuery($this->conn, $this->schema);
    $converted_data = $query
      ->selectColumn('anything.data')
      ->from('anything')
      ->fetchValue();
    
    $this->assertEquals($data, $converted_data);
  }
}
