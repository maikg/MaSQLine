<?PHP
namespace MaSQLine\Tests\Queries;

use MaSQLine\Queries\Query;
use MaSQLine\Queries\InsertQuery;
use MaSQLine\Queries\DeleteQuery;
use Doctrine\DBAL\Types\Type;

class ManipulationQueryTest extends \MaSQLine\Tests\TestCase {
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
  
  
  public function testDelete() {
    $this->insertPostFixtures();
    
    $dt = new \DateTime('1 January 2000 00:00:00');
    
    $query = new DeleteQuery($this->conn, $this->schema);
    $query
      ->setTableName('posts')
      ->where(function($where) use ($dt) {
        $where->smallerThan('posts.posted_at', $dt);
      });
    
    $sql = $query->toSQL();
    
    $expected_sql = "DELETE FROM `posts` WHERE `posts`.`posted_at` < ?";
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array($dt);
    $this->assertEquals($expected_values, $query->getParamValues());
    
    $expected_types = array(Type::getType('datetime'));
    $this->assertEquals($expected_types, $query->getParamTypes());
    
    $row_count = $query->execute();
    $this->assertEquals(1, $row_count);
  }
}
