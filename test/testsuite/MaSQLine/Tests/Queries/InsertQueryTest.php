<?PHP
namespace MaSQLine\Tests\Queries;

use MaSQLine\Queries\Query;
use MaSQLine\Queries\InsertQuery;
use Doctrine\DBAL\Types\Type;

class ManipulationQueryTest extends \PHPUnit_Framework_TestCase {
  private $schema;
  
  
  public function setUp() {
    $this->conn = \Doctrine\DBAL\DriverManager::getConnection(
      array(
        'driver'    => 'pdo_sqlite',
        'db_name'   => 'dbalext_tests',
        'memory'    => true
      ),
      new \Doctrine\DBAL\Configuration()
    );
    $this->schema = require \TEST_ROOT_PATH . '/fixtures/schema.php';
    
    $queries = $this->schema->toSql($this->conn->getDatabasePlatform());
    foreach ($queries as $query) {
      $this->conn->executeQuery($query);
    }
  }
  
  
  public function testInsert() {
    $dt = new \DateTime();
    
    $query = new InsertQuery($this->conn, $this->schema);
    $sql = $query
      ->setTableName('posts')
      ->setValues(array(
        'author_id'   => 2,
        'title'       => 'Foo',
        'body'        => 'Bar',
        'posted_at'   => $dt
      ))
      ->toSQL();
    
    $expected_sql = "INSERT INTO `posts` (`author_id`, `title`, `body`, `posted_at`) VALUES (?, ?, ?, ?)";
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(2, 'Foo', 'Bar', $dt);
    $this->assertEquals($expected_values, $query->getParamValues());
    
    $expected_types = array(
      Type::getType('integer'),
      Type::getType('string'),
      Type::getType('text'),
      Type::getType('datetime')
    );
    $this->assertEquals($expected_types, $query->getParamTypes());
    
    $row_count = $query->execute();
    $this->assertEquals(1, $row_count);
  }
  
  
  /**
   * @expectedException \Doctrine\DBAL\Schema\SchemaException
   */
  public function testInvalidColumn() {
    $query = new InsertQuery($this->conn, $this->schema);
    $sql = $query
      ->setTableName('posts')
      ->setValues(array(
        'author_id'   => 2,
        'title'       => 'Foo',
        'bodyzzz'     => 'Bar',
        'posted_at'   => new \DateTime()
      ))
      ->execute();
  }
}
