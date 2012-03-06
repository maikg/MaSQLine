<?PHP
$__create_schema = function() {
  $schema = new \Doctrine\DBAL\Schema\Schema();
  
  $anything = $schema->createTable('anything');
  $anything->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
  $anything->addColumn('data', 'json');
  $anything->setPrimaryKey(array('id'));
  
  return $schema;
};

return $__create_schema();
