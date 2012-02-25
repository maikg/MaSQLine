<?PHP
namespace MaSQLine {
  define(__NAMESPACE__ . '\ROOT_PATH', __DIR__ . '/..');
  define(__NAMESPACE__ . '\TEST_ROOT_PATH', __DIR__);
  
  $loader = require_once __DIR__ . '/../vendor/.composer/autoload.php';
  $loader->add('MaSQLine', __DIR__ . '/../lib');
  
  require_once TEST_ROOT_PATH . '/testsuite/MaSQLine/Tests/TestCase.php';
}
