<?PHP
namespace MaSQLine\Tests\Queries;

use MaSQLine\Queries\ExpressionBuilder;
use MaSQLine\Queries\Expression;
use Doctrine\DBAL\Types\Type;

class ConditionsBuilderTest extends \MaSQLine\Tests\TestCase {
  private $builder;
  
  
  public function setUp() {
    $this->setUpWithSchemaFixture('schema');
    
    $this->builder = new ExpressionBuilder($this->schema);
  }
  
  
  /**
   * @dataProvider simpleConditionProvider
   */
  public function testSimpleConditions($condition_method, $operator) {
    $expr = $this->builder->$condition_method('posts.id', 2);
    $this->assertEquals(sprintf('`posts`.`id` %s ?', $operator), $expr->getFormat());
    $this->assertEquals(array(2), $expr->getValues());
    $this->assertEquals(array(Type::getType('integer')), $expr->getTypes());
  }
  
  
  public static function simpleConditionProvider() {
    return array(
      array('equalTo', '='),
      array('eq', '='),
      array('greaterThan', '>'),
      array('gt', '>'),
      array('greaterThanOrEqualTo', '>='),
      array('gte', '>='),
      array('lessThan', '<'),
      array('lt', '<'),
      array('lessThanOrEqualTo', '<='),
      array('lte', '<=')
    );
  }
  
  
  public function testNegateExpression() {
    $orig_expr = new Expression('`posts`.`id` = ?', array(2), array(Type::getType('integer')));
    $expr = $this->builder->not($orig_expr);
    $this->assertEquals('NOT (`posts`.`id` = ?)', $expr->getFormat());
    $this->assertEquals(array(2), $expr->getValues());
    $this->assertEquals(array(Type::getType('integer')), $expr->getTypes());
  }
  
  
  public function testInExpression() {
    $expr = $this->builder->in('posts.id', array(2, 3, 4));
    $this->assertEquals('`posts`.`id` IN (?,?,?)', $expr->getFormat());
    $this->assertEquals(array(2,3,4), $expr->getValues());
    $this->assertEquals(array(Type::getType('integer'), Type::getType('integer'), Type::getType('integer')), $expr->getTypes());
  }
  
  
  /**
   * @dataProvider simpleColumnConditionProvider
   */
  public function testSimpleColumnConditions($condition_method, $operator) {
    $expr = $this->builder->$condition_method('posts.id', 'comments.id');
    $this->assertEquals(sprintf('`posts`.`id` %s `comments`.`id`', $operator), $expr->getFormat());
    $this->assertEquals(array(), $expr->getValues());
    $this->assertEquals(array(), $expr->getTypes());
  }
  
  
  public static function simpleColumnConditionProvider() {
    return array(
      array('equalToColumn', '='),
      array('eqCol', '='),
      array('greaterThanColumn', '>'),
      array('gtCol', '>'),
      array('greaterThanOrEqualToColumn', '>='),
      array('gteCol', '>='),
      array('lessThanColumn', '<'),
      array('ltCol', '<'),
      array('lessThanOrEqualToColumn', '<='),
      array('lteCol', '<=')
    );
  }
  
  
  public function testLikeExpression() {
    $expr = $this->builder->like('posts.title', 'Foo%');
    $this->assertEquals('`posts`.`title` LIKE ?', $expr->getFormat());
    $this->assertEquals(array('Foo%'), $expr->getValues());
    $this->assertEquals(array(Type::getType('string')), $expr->getTypes());
  }
  
  
  /**
   * @dataProvider nullExpressionProvider
   */
  public function testNullExpression($condition_method, $operator) {
    $expr = $this->builder->$condition_method('posts.title');
    $this->assertEquals(sprintf('`posts`.`title` %s', $operator), $expr->getFormat());
    $this->assertEquals(array(), $expr->getValues());
    $this->assertEquals(array(), $expr->getTypes());
  }
  
  
  public static function nullExpressionProvider() {
    return array(
      array('isNull', 'IS NULL'),
      array('null', 'IS NULL')
    );
  }
  
  
  public function testAllExpression() {
    $expr1 = new Expression('`posts.author_id` = ?', array(2), array(Type::getType('integer')));
    $expr2 = new Expression('`posts.title` LIKE ?', array('Foo%'), array(Type::getType('string')));
    
    $expr = $this->builder->all($expr1, $expr2);
    
    $this->assertInstanceOf('\MaSQLine\Queries\CompoundExpression', $expr);
    $this->assertEquals(array($expr1, $expr2), $expr->getExpressions());
    $this->assertEquals(sprintf('(%s AND %s)', $expr1->getFormat(), $expr2->getFormat()), $expr->getFormat());
    $this->assertEquals(array(2, 'Foo%'), $expr->getValues());
    $this->assertEquals(array(Type::getType('integer'), Type::getType('string')), $expr->getTypes());
  }
  
  
  public function testAnyExpression() {
    $expr1 = new Expression('`posts.author_id` = ?', array(2), array(Type::getType('integer')));
    $expr2 = new Expression('`posts.title` LIKE ?', array('Foo%'), array(Type::getType('string')));
    
    $expr = $this->builder->any(
      $expr1,
      $expr2
    );
    
    $this->assertInstanceOf('\MaSQLine\Queries\CompoundExpression', $expr);
    $this->assertEquals(array($expr1, $expr2), $expr->getExpressions());
    $this->assertEquals(sprintf('(%s OR %s)', $expr1->getFormat(), $expr2->getFormat()), $expr->getFormat());
    $this->assertEquals(array(2, 'Foo%'), $expr->getValues());
    $this->assertEquals(array(Type::getType('integer'), Type::getType('string')), $expr->getTypes());
  }
  
  
  public function testNestedCompoundExpressions() {
    $expr1a = new Expression('`posts.author_id` = ?', array(2), array(Type::getType('integer')));
    $expr1b = new Expression('`posts.title` LIKE ?', array('Foo%'), array(Type::getType('string')));
    
    $expr1 = $this->builder->all($expr1a, $expr1b);
    
    $expr2a = new Expression('`posts.author_id` = ?', array(3), array(Type::getType('integer')));
    $expr2b = new Expression('`posts.title` LIKE ?', array('Bar%'), array(Type::getType('string')));
    
    $expr2 = $this->builder->all($expr2a, $expr2b);
    
    $expr = $this->builder->any($expr1, $expr2);
    
    $this->assertEquals(sprintf('((%s AND %s) OR (%s AND %s))', $expr1a->getFormat(), $expr1b->getFormat(), $expr2a->getFormat(), $expr2b->getFormat()), $expr->getFormat());
    $this->assertEquals(array(2, 'Foo%', 3, 'Bar%'), $expr->getValues());
    $this->assertEquals(array(Type::getType('integer'), Type::getType('string'), Type::getType('integer'), Type::getType('string')), $expr->getTypes());
  }
  
  
  public function testRawColumnExpressions() {
    $expr = $this->builder->eq($this->builder->raw('COUNT(*)'), 2, 'integer');
    $this->assertEquals('COUNT(*) = ?', $expr->getFormat());
    $this->assertEquals(array(2), $expr->getValues());
    $this->assertEquals(array(Type::getType('integer')), $expr->getTypes());
  }
}
