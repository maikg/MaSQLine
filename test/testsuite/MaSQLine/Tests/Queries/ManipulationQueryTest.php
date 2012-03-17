<?PHP
namespace MaSQLine\Tests\Queries;

use MaSQLine\Queries\Query;
use MaSQLine\Queries\InsertQuery;
use MaSQLine\Queries\UpdateQuery;
use MaSQLine\Queries\DeleteQuery;
use Doctrine\DBAL\Types\Type;

class ManipulationQueryTest extends \MaSQLine\Tests\TestCase {
  public function setUp() {
    $this->setUpWithSchemaFixture('schema');
  }
  
  
  public function testInsert() {
    $dt = new \DateTime();
    
    $query = new InsertQuery($this->conn, $this->schema, 'posts');
    $sql = $query
      ->setValues(array(
        'author_id'   => 2,
        'title'       => 'Foo',
        'body'        => 'Bar',
        'posted_at'   => $dt
      ))
      ->getFormat();
    
    $expected_sql = "INSERT INTO `posts` (`author_id`, `title`, `body`, `posted_at`) VALUES (?, ?, ?, ?)";
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(2, 'Foo', 'Bar', $dt);
    $this->assertEquals($expected_values, $query->getValues());
    
    $expected_types = array(
      Type::getType('integer'),
      Type::getType('string'),
      Type::getType('text'),
      Type::getType('datetime')
    );
    $this->assertEquals($expected_types, $query->getTypes());
    
    $row_count = $query->execute();
    $this->assertEquals(1, $row_count);
  }
  
  
  /**
   * @expectedException \Doctrine\DBAL\Schema\SchemaException
   */
  public function testInvalidColumn() {
    $query = new InsertQuery($this->conn, $this->schema, 'posts');
    $sql = $query
      ->setValues(array(
        'author_id'   => 2,
        'title'       => 'Foo',
        'bodyzzz'     => 'Bar',
        'posted_at'   => new \DateTime()
      ))
      ->execute();
  }
  
  
  public function testDelete() {
    $this->insertPostFixtures();
    
    $dt = new \DateTime('1 January 2000 00:00:00');
    
    $query = new DeleteQuery($this->conn, $this->schema, 'posts');
    $query
      ->where(function($where) use ($dt) {
        return $where->lt('posts.posted_at', $dt);
      });
    
    $sql = $query->getFormat();
    
    $expected_sql = "DELETE FROM `posts` WHERE `posts`.`posted_at` < ?";
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array($dt);
    $this->assertEquals($expected_values, $query->getValues());
    
    $expected_types = array(Type::getType('datetime'));
    $this->assertEquals($expected_types, $query->getTypes());
    
    $row_count = $query->execute();
    $this->assertEquals(1, $row_count);
  }
  
  
  public function testUpdate() {
    $this->insertPostFixtures();
    
    $dt = new \DateTime();
    
    $query = new UpdateQuery($this->conn, $this->schema, 'posts');
    $query
      ->setValues(array(
        'author_id' => 4,
        'posted_at' => $dt
      ))
      ->where(function($where) {
        return $where->eq('posts.id', 1);
      });
    
    $sql = $query->getFormat();
    
    $expected_sql = "UPDATE `posts` SET `author_id` = ?, `posted_at` = ? WHERE `posts`.`id` = ?";
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(4, $dt, 1);
    $this->assertEquals($expected_values, $query->getValues());
    
    $expected_types = array(Type::getType('integer'), Type::getType('datetime'), Type::getType('integer'));
    $this->assertEquals($expected_types, $query->getTypes());
    
    $row_count = $query->execute();
    $this->assertEquals(1, $row_count);
  }
}
