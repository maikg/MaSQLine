<?PHP
define('ROOT_PATH', __DIR__ . '/..');
define('TEST_ROOT_PATH', __DIR__);

$loader = require_once __DIR__ . '/../vendor/.composer/autoload.php';
$loader->add('DBALExt', __DIR__ . '/../lib');
