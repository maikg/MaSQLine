<?PHP
namespace MaSQLine\Tests;

use MaSQLine\DB;
use Doctrine\DBAL\Types\Type;

class DBTest extends TestCase {
  public function setUp() {
    $this->setUpWithSchemaFixture('schema');
    
    $this->db = new DB($this->conn, $this->schema);
  }
  
  
  public function testReturnsConstructorArguments() {
    $this->assertSame($this->conn, $this->db->getConnection());
    $this->assertSame($this->schema, $this->db->getSchema());
  }
  
  
  /**
   * @dataProvider queryShortcutProvider
   */
  public function testQueryShortcut($factory_method, $expected_class, array $args = array()) {
    $expected_class = sprintf('\MaSQLine\Queries\%s', $expected_class);
    
    $query = call_user_func_array(array($this->db, $factory_method), $args);

    $this->assertSame($this->conn, $query->getConnection());
    $this->assertSame($this->schema, $query->getSchema());
    $this->assertInstanceOf($expected_class, $query);
  }
  
  
  public static function queryShortcutProvider() {
    return array(
      array('createSelectQuery', 'SelectQuery'),
      array('createInsertQuery', 'InsertQuery', array('posts')),
      array('createUpdateQuery', 'UpdateQuery', array('posts')),
      array('createDeleteQuery', 'DeleteQuery', array('posts'))
    );
  }
  
  
  public function testSimpleSelect() {
    $query = $this->db->simpleSelect('authors');
    
    $expected_sql = <<<SQL
SELECT `authors`.`id`, `authors`.`username`
FROM `authors`
SQL;
    
    $this->assertInstanceOf('\MaSQLine\Queries\SelectQuery', $query);
    $this->assertEquals($expected_sql, $query->toSQL());
    $this->assertEquals(array(), $query->getValues());
    $this->assertEquals(array(), $query->getTypes());
    
    $query = $this->db->simpleSelect('authors', array('id' => 1));
    
    $expected_sql = <<<SQL
SELECT `authors`.`id`, `authors`.`username`
FROM `authors`
WHERE (`authors`.`id` = ?)
SQL;
    
    $this->assertInstanceOf('\MaSQLine\Queries\SelectQuery', $query);
    $this->assertEquals($expected_sql, $query->toSQL());
    $this->assertEquals(array(1), $query->getValues());
    $this->assertEquals(array(Type::getType('integer')), $query->getTypes());
  }
  
  
  public function testInsert() {
    $affected_rows = $this->db->insert('authors', array('username' => 'Pop-Eye'));
    $this->assertEquals(1, $affected_rows);
    
    $result = $this->db->simpleSelect('authors', array('username' => 'Pop-Eye'))->fetchOne();
    
    $this->assertNotNull($result);
  }
  
  
  public function testUpdate() {
    $this->insertPostFixtures();
    
    $affected_rows = $this->db->update('posts', array('title' => 'Awesome'), array('id' => 1));
    $this->assertEquals(1, $affected_rows);
    
    $result = $this->db->simpleSelect('posts', array('id' => 1))->fetchOne();
    
    $this->assertEquals('Awesome', $result['title']);
  }
  
  
  public function testDelete() {
    $this->insertPostFixtures();
    
    $affected_rows = $this->db->delete('posts', array('id' => 1));
    $this->assertEquals(1, $affected_rows);
    
    $result = $this->db->simpleSelect('posts', array('id' => 1))->fetchOne();
    
    $this->assertNull($result);
  }
  
  
  public function testExpressionBuilder() {
    $builder = $this->db->expr();
    
    $this->assertInstanceOf('\MaSQLine\Queries\ExpressionBuilder', $builder);
    $this->assertSame($this->schema, $builder->getSchema());
  }
  
  
  public function testRegistry() {
    DB::register('db1', $this->db);
    $this->assertSame($this->db, DB::get('db1'));
    
    $this->assertSame($this->db, DB::getDefault());
    
    DB::deregister('db1');
    
    try {
      $this->assertNull(DB::get('db1'));
      $this->fail("Expected InvalidArgumentException to be thrown.");
    }
    catch (\InvalidArgumentException $e) {}
    
    try {
      $this->assertNull(DB::getDefault());
      $this->fail("Expected RuntimeException to be thrown.");
    }
    catch (\RuntimeException $e) {}
  }
  
  
  public function testRegistryLazyEntries() {
    $db = $this->db;
    DB::register('db1', function() use ($db) {
      return $db;
    });
    
    $this->assertSame($this->db, DB::get('db1'));
  }
}
