<?PHP
$__create_schema = function() {
  $schema = new \Doctrine\DBAL\Schema\Schema();
  
  $authors = $schema->createTable('authors');
  $authors->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
  $authors->addColumn('username', 'string');
  $authors->setPrimaryKey(array('id'));
  
  $posts = $schema->createTable('posts');
  $posts->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
  $posts->addColumn('author_id', 'integer', array('unsigned' => true));
  $posts->addColumn('title', 'string');
  $posts->addColumn('body', 'text');
  $posts->addColumn('posted_at', 'datetime');
  $posts->setPrimaryKey(array('id'));
  
  $comments = $schema->createTable('comments');
  $comments->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
  $comments->addColumn('post_id', 'integer', array('unsigned' => true));
  $comments->addColumn('author', 'string');
  $comments->addColumn('body', 'text');
  $comments->addColumn('posted_at', 'datetime');
  $comments->setPrimaryKey(array('id'));
  
  return $schema;
};

return $__create_schema();
