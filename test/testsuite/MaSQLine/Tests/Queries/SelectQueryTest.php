<?PHP
namespace MaSQLine\Tests\Queries;

use MaSQLine\Queries\Query;
use MaSQLine\Queries\Expression;
use MaSQLine\Queries\SelectQuery;
use Doctrine\DBAL\Types\Type;

class SelectQueryTest extends \MaSQLine\Tests\TestCase {
  public function setUp() {
    $this->setUpWithSchemaFixture('schema');
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
SELECT `authors`.`id`, `authors`.`username`
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
      ->selectColumn('posts.id', Type::getType('string'))
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
      ->selectColumn('posts.id', 'string')
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
      ->selectColumn(array(Expression::raw('COUNT(*)') => 'num'), 'integer')
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
      ->select(array(Expression::raw('COUNT(*)') => 'num'))
      ->from('posts')
      ->toSQL();
  }
  
  
  public function testWhereClause() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.title')
      ->from('posts')
      ->where($query->expr()->eq('posts.id', 5))
      ->toSQL();
      
    $expected_sql = <<<SQL
SELECT `posts`.`title`
FROM `posts`
WHERE `posts`.`id` = ?
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(5);
    $this->assertEquals($expected_values, $query->getValues());
    
    $expected_types = array(Type::getType('integer'));
    $this->assertEquals($expected_types, $query->getTypes());
  }
  
  
  /**
   * @dataProvider joinProvider
   */
  public function testSimpleInnerJoin($join_method, $join_clause) {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('comments.id', 'posts.title')
      ->from('comments')
      ->$join_method('comments.post_id', 'posts.id')
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `comments`.`id`, `posts`.`title`
FROM `comments`
%s `posts` ON `comments`.`post_id` = `posts`.`id`
SQL;
    
    $this->assertEquals(sprintf($expected_sql, $join_clause), $sql);
  }
  
  
  /**
   * @dataProvider joinProvider
   */
  public function testComplexJoin($join_method, $join_clause) {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('comments.id', 'posts.title')
      ->from('comments')
      ->$join_method('posts', $query->expr()->all(
        $query->expr()->eqCol('comments.post_id', 'posts.id'),
        $query->expr()->eq('posts.author_id', 2)
      ))
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `comments`.`id`, `posts`.`title`
FROM `comments`
%s `posts` ON (`comments`.`post_id` = `posts`.`id` AND `posts`.`author_id` = ?)
SQL;
    
    $this->assertEquals(sprintf($expected_sql, $join_clause), $sql);
    
    $expected_values = array(2);
    $this->assertEquals($expected_values, $query->getValues());
    
    $expected_types = array(Type::getType('integer'));
    $this->assertEquals($expected_types, $query->getTypes());
  }
  
  
  public static function joinProvider() {
    return array(
      array('innerJoin', 'INNER JOIN'),
      array('leftJoin', 'LEFT JOIN')
    );
  }
  
  
  public function testMixWhereAndJoinParams() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('comments.id', 'posts.title')
      ->from('comments')
      ->where($query->expr()->like('comments.body', 'Foo%'))
      ->innerJoin('posts', $query->expr()->all(
        $query->expr()->eqCol('comments.post_id', 'posts.id'),
        $query->expr()->eq('posts.author_id', 2)
      ))
      ->innerJoin('posts.author_id', 'authors.id')
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `comments`.`id`, `posts`.`title`
FROM `comments`
INNER JOIN `posts` ON (`comments`.`post_id` = `posts`.`id` AND `posts`.`author_id` = ?)
INNER JOIN `authors` ON `posts`.`author_id` = `authors`.`id`
WHERE `comments`.`body` LIKE ?
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(2, 'Foo%');
    $this->assertEquals($expected_values, $query->getValues());
    
    $expected_types = array(Type::getType('integer'), Type::getType('text'));
    $this->assertEquals($expected_types, $query->getTypes());
  }
  
  
  public function testLimit() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.id')
      ->from('posts')
      ->limit(3)
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `posts`.`id`
FROM `posts`
LIMIT 3
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.id')
      ->from('posts')
      ->offset(10)
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `posts`.`id`
FROM `posts`
LIMIT 10,%d
SQL;
    
    $this->assertEquals(sprintf($expected_sql, PHP_INT_MAX), $sql);
    
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.id')
      ->from('posts')
      ->limit(10)
      ->offset(20)
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `posts`.`id`
FROM `posts`
LIMIT 20,10
SQL;
    
    $this->assertEquals($expected_sql, $sql);
  }
  
  
  public function testOrderBy() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.id', 'posts.title')
      ->from('posts')
      ->orderBy('-posts.posted_at', '+title', 'id')
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `posts`.`id`, `posts`.`title`
FROM `posts`
ORDER BY `posts`.`posted_at` DESC, `title` ASC, `id` ASC
SQL;
    
    $this->assertEquals($expected_sql, $sql);
  }
  
  
  public function testGroupByAndHaving() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.author_id')
      ->selectAggr('MIN', 'posts.posted_at', 'first_posted_at')
      ->from('posts')
      ->groupBy('posts.author_id')
      ->having($query->expr()->gt(Expression::raw('COUNT(*)'), 3, 'integer'))
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `posts`.`author_id`, MIN(`posts`.`posted_at`) AS `first_posted_at`
FROM `posts`
GROUP BY `posts`.`author_id`
HAVING COUNT(*) > ?
SQL;
    
    $this->assertEquals($expected_sql, $sql);
    
    $expected_values = array(3);
    $this->assertEquals($expected_values, $query->getValues());

    $expected_types = array(Type::getType('integer'));
    $this->assertEquals($expected_types, $query->getTypes());
  }
  
  
  public function testAggregateShortcuts() {
    $query = new SelectQuery($this->conn, $this->schema);
    $sql = $query
      ->select('posts.author_id')
      ->selectAggr('MIN', 'posts.posted_at')
      ->selectAggr('MAX', 'posts.posted_at', 'last_posted_at')
      ->selectCount(NULL, 'all_count')
      ->selectCount('posts.id', 'post_id_count')
      ->from('posts')
      ->groupBy('posts.author_id')
      ->toSQL();
    
    $expected_sql = <<<SQL
SELECT `posts`.`author_id`, MIN(`posts`.`posted_at`), MAX(`posts`.`posted_at`) AS `last_posted_at`, COUNT(*) AS `all_count`, COUNT(`posts`.`id`) AS `post_id_count`
FROM `posts`
GROUP BY `posts`.`author_id`
SQL;
    
    $this->assertEquals(sprintf($expected_sql, 'MIN'), $sql);
    
    $expected_types = array(
      'author_id'                   => Type::getType('integer'),
      'MIN(`posts`.`posted_at`)'    => Type::getType('datetime'),
      'last_posted_at'              => Type::getType('datetime'),
      'all_count'                   => Type::getType('integer'),
      'post_id_count'               => Type::getType('integer')
    );
    $this->assertEquals($expected_types, $query->getConversionTypes());
  }
  
  
  public function testFetchAll() {
    $this->insertPostFixtures();
    
    $query = new SelectQuery($this->conn, $this->schema);
    $query
      ->select('posts.*')
      ->from('posts')
      ->orderBy('posts.id');
    
    $rows = $query->fetchAll();
    
    $this->assertCount(2, $rows);
    
    $this->assertSame(1, $rows[0]['id']);
    $this->assertSame(2, $rows[0]['author_id']);
    $this->assertSame('Foo', $rows[0]['title']);
    $this->assertSame('Bar', $rows[0]['body']);
    $this->assertInstanceOf('\DateTime', $rows[0]['posted_at']);
    
    $rows = $query->fetchAll('id');
    $this->assertEquals(array(1, 2), array_keys($rows));
    
    $this->assertSame('FooBar', $rows[2]['title']);
  }
  
  
  public function testFetchAllNoResult() {
    $query = new SelectQuery($this->conn, $this->schema);
    $query
      ->select('posts.*')
      ->from('posts')
      ->where($query->expr()->eq('posts.title', 'asdfasdf'));
    
    $rows = $query->fetchAll('id');
    $this->assertCount(0, $rows);
  }
  
  
  public function testFetchOne() {
    $this->insertPostFixtures();
    
    $query = new SelectQuery($this->conn, $this->schema);
    $row = $query
      ->select('posts.*')
      ->from('posts')
      ->orderBy('posts.id')
      ->fetchOne();
      
    $this->assertInternalType('array', $row);
    
    $this->assertSame(1, $row['id']);
    $this->assertSame(2, $row['author_id']);
    $this->assertSame('Foo', $row['title']);
    $this->assertSame('Bar', $row['body']);
    $this->assertInstanceOf('\DateTime', $row['posted_at']);
  }
  
  
  public function testFetchList() {
    $this->insertPostFixtures();
    
    $query = new SelectQuery($this->conn, $this->schema);
    $rows = $query
      ->select('posts.*')
      ->from('posts')
      ->orderBy('posts.id')
      ->fetchList('title');
    
    $this->assertEquals(array('Foo', 'FooBar'), $rows);
    
    $query = new SelectQuery($this->conn, $this->schema);
    $rows = $query
      ->select('posts.title', 'posts.id')
      ->from('posts')
      ->orderBy('posts.id')
      ->fetchList();
    
    $this->assertEquals(array('Foo', 'FooBar'), $rows);
    
    $query = new SelectQuery($this->conn, $this->schema);
    $rows = $query
      ->select('posts.*')
      ->from('posts')
      ->orderBy('-posts.id')
      ->fetchList('title', 'id');
    
    $this->assertEquals(array(1 => 'Foo', 2 => 'FooBar'), $rows);
    
    $query = new SelectQuery($this->conn, $this->schema);
    $rows = $query
      ->select('posts.title', 'posts.id')
      ->from('posts')
      ->orderBy('-posts.id')
      ->fetchList(NULL, 'id');
    
    $this->assertEquals(array(1 => 'Foo', 2 => 'FooBar'), $rows);
  }
  
  
  public function testFetchValue() {
    $this->insertPostFixtures();
    
    $query = new SelectQuery($this->conn, $this->schema);
    $count = $query
      ->selectCount()
      ->from('posts')
      ->orderBy('posts.id')
      ->fetchValue();
    
    $this->assertEquals(2, $count);
    
    $query = new SelectQuery($this->conn, $this->schema);
    $num = $query
      ->selectAggr('MAX', 'posts.posted_at', 'last_posted_at')
      ->selectCount(NULL, 'num')
      ->from('posts')
      ->orderBy('posts.id')
      ->fetchValue('num');
    
    $this->assertEquals(2, $num);
  }
  
  
  public function testFetchValueNoResult() {
    $this->insertPostFixtures();
    
    $query = new SelectQuery($this->conn, $this->schema);
    $title = $query
      ->select('posts.title')
      ->from('posts')
      ->orderBy('posts.id')
      ->where($query->expr()->eq('posts.title', 'asdfasdf'))
      ->fetchValue();
    
    $this->assertNull($title);
  }
}
