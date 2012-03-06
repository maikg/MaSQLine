<?PHP
namespace MaSQLine\Tests;

use Doctrine\DBAL\Types\Type;

class TestCase extends \PHPUnit_Framework_TestCase {
  protected $conn;
  protected $schema;
  
  
  protected function setUpWithSchemaFixture($schema_name) {
    $this->conn = \Doctrine\DBAL\DriverManager::getConnection(
      array(
        'driver'    => 'pdo_sqlite',
        'db_name'   => 'masqline_tests',
        'memory'    => true
      ),
      new \Doctrine\DBAL\Configuration()
    );
    $this->schema = require \MaSQLine\TEST_ROOT_PATH . '/fixtures/' . $schema_name . '.php';
    
    $queries = $this->schema->toSql($this->conn->getDatabasePlatform());
    foreach ($queries as $query) {
      $this->conn->executeQuery($query);
    }
  }
  
  
  protected function insertPostFixtures() {
    $this->conn->insert(
      'posts',
      array(
        'id' => 1,
        'author_id' => 2,
        'title' => 'Foo',
        'body' => 'Bar',
        'posted_at' => new \DateTime('31 December 1999 23:59:59')
      ),
      array(
        Type::getType('integer'),
        Type::getType('integer'),
        Type::getType('string'),
        Type::getType('text'),
        Type::getType('datetime')
      )
    );
    
    $this->conn->insert(
      'posts',
      array(
        'id' => 2,
        'author_id' => 3,
        'title' => 'FooBar',
        'body' => 'Test article',
        'posted_at' => new \DateTime('1 January 2000 00:00:00')
      ),
      array(
        Type::getType('integer'),
        Type::getType('integer'),
        Type::getType('string'),
        Type::getType('text'),
        Type::getType('datetime')
      )
    );
  }
}
