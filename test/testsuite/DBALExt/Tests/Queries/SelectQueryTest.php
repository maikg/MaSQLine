<?PHP
namespace DBALExt\Tests\Queries;

use DBALExt\Queries\Query;
use DBALExt\Queries\SelectQuery;
use Doctrine\DBAL\Types\Type;

class SelectQueryTest extends \PHPUnit_Framework_TestCase {
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
  }
  
  
  public function testSimple() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.id', 'posts.posted_at')
      ->from('posts')
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `posts`.`id`, `posts`.`posted_at`
FROM `posts`
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_types = array(
      'id'        => Type::getType('integer'),
      'posted_at' => Type::getType('datetime')
    );
    $this->assertEquals($expected_types, $query->getConversionTypes());
  }
  
  
  public function testAlias() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select(array('posts.id' => 'post_id'), 'posts.posted_at')
      ->from('posts')
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `posts`.`id` AS `post_id`, `posts`.`posted_at`
FROM `posts`
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_types = array(
      'post_id'   => Type::getType('integer'),
      'posted_at' => Type::getType('datetime')
    );
    $this->assertEquals($expected_types, $query->getConversionTypes());
  }
  
  
  public function testWildcard() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('authors.*')
      ->from('authors')
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `authors`.*
FROM `authors`
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_types = array(
      'id'        => Type::getType('integer'),
      'username'  => Type::getType('string')
    );
    $this->assertEquals($expected_types, $query->getConversionTypes());
  }
  
  
  /**
   * @expectedException \InvalidArgumentException
   */
  public function testWildcardAlias() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select(array('authors.*' => 'writers'))
      ->from('authors')
      ->toSQL();
  }
  
  
  public function testCustomTypeForSelect() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select()
      ->addSelect('posts.id', Type::getType('string'))
      ->from('posts')
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `posts`.`id`
FROM `posts`
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_types = array(
      'id'   => Type::getType('string')
    );
    $this->assertEquals($expected_types, $query->getConversionTypes());
  }
  
  
  public function testCustomStringTypeForSelect() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select()
      ->addSelect('posts.id', 'string')
      ->from('posts')
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `posts`.`id`
FROM `posts`
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_types = array(
      'id' => Type::getType('string')
    );
    $this->assertEquals($expected_types, $query->getConversionTypes());
  }
  
  
  public function testRawSQLExpressions() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select()
      ->addSelect(array(Query::raw('COUNT(*)') => 'num'), 'integer')
      ->from('posts')
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT COUNT(*) AS `num`
FROM `posts`
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_types = array(
      'num' => Type::getType('integer')
    );
    $this->assertEquals($expected_types, $query->getConversionTypes());
  }
  
  
  /**
   * @expectedException \InvalidArgumentException
   */
  public function testRawSQLExpressionWithoutType() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select(array(Query::raw('COUNT(*)') => 'num'))
      ->from('posts')
      ->toSQL();
  }
  
  
  /**
   * @dataProvider simpleConditionProvider
   */
  public function testSimpleConditions($condition_method, $operator) {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.title')
      ->from('posts')
      ->$condition_method('posts.id', 5)
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `posts`.`title`
FROM `posts`
WHERE `posts`.`id` %s ?
SQL;
    
    $this->assertEquals(sprintf($expected_sql, $operator), $sql);
    
    $expected_values = array(5);
    $this->assertEquals($expected_values, $query->getParamValues());
    
    $expected_types = array(Type::getType('integer'));
    $this->assertEquals($expected_types, $query->getParamTypes());
  }
  
  
  public static function simpleConditionProvider() {
    return array(
      array('whereEquals', '='),
      array('whereNotEquals', '<>'),
      array('whereGreaterThan', '>'),
      array('whereSmallerThan', '<'),
      array('whereGreaterThanOrEquals', '>='),
      array('whereSmallerThanOrEquals', '<=')
    );
  }
  
  
  public function testWhereInIntArray() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.title')
      ->from('posts')
      ->whereIn('posts.id', array(2,3,4))
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `posts`.`title`
FROM `posts`
WHERE `posts`.`id` IN (?)
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(array(2,3,4));
    $this->assertEquals($expected_values, $query->getParamValues());
    
    $expected_types = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
    $this->assertEquals($expected_types, $query->getParamTypes());
  }
  
  
  public function testWhereInStringArray() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.id')
      ->from('posts')
      ->whereIn('posts.title', array('Foo', 'Bar'))
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `posts`.`id`
FROM `posts`
WHERE `posts`.`title` IN (?)
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(array('Foo', 'Bar'));
    $this->assertEquals($expected_values, $query->getParamValues());
    
    $expected_types = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
    $this->assertEquals($expected_types, $query->getParamTypes());
  }
}
