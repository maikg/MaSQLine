# MaSQLine

MaSQLine is a utility library built on top of Doctrine DBAL. It tries to find a sweet spot between managing all database
communication manually and using full-blown object-relational mapping (ORM).

MaSQLine requires PHP 5.3+.

> **WARNING:** MaSQLine currently doesn't work with the latest 2.2.1 release of DBAL (and probably not with any DBAL
> release) due to a bug in DBAL. This is fixed by [my pull request](https://github.com/doctrine/dbal/pull/120), but
> until then, you'll have to apply this fix manually after installing the DBAL dependency.

## Why?

What I don't like about using ORM is that eventually, you end up with an API that isn't completely transparent about
when queries are executed. As application complexity increases and entity objects are passed around your code a lot, you
can't always be sure that accessing an entity's relation will result in another call to the database. Of course, if
that's the case, you could do some optimization like including the related record when performing the initial query,
but that query might have been executed in a totally different part of your code which isn't concerned with including
the related objects.

Also, at the point your application is getting successful, it becomes very difficult to optimize slow queries without
resorting to writing custom SQL in your code, which kind of defeats the purpose of using an ORM in the first place.

What I do like about ORM is the automatic type coercion between PHP and database types, and the code portability that
results from accessing the database through an abstraction layer and letting that layer take care of things like
connecting and escaping. This last point is particularly helpful when running tests against a different database (say, a
SQLite in-memory database) than application code. I also like the concept of specifying my data model in PHP, as that
provides me with a lot of information that can easily be accessed by other scripts.

Doctrine's DBAL library provides a nice base to work from, as it helps with code portability and type coercion. MaSQLine
builds on top of DBAL to introduce several ORM features that are missing from DBAL, without pulling in a full ORM
library.

## Installation

MaSQLine uses [Composer](http://packagist.org/about-composer) to manage its dependencies. You can
[install](https://github.com/composer/composer/blob/master/README.md) Composer system-wide or just add `composer.phar`
to the root of this project after cloning. Afterwards, you simply call `php composer.phar install` in the project
root to install the necessary dependencies.

MaSQLine is not up at [Packagist](http://packagist.org/) at the moment, but you can easily specify MaSQLine as a
dependency in your own `composer.json` by adding this GitHub project as a repository.

```json
{
    // ...
    "repositories": [
    {
      "type": "vcs",
      "url": "git://github.com/maikg/MaSQLine.git"
    }
    ],
    "require": {
      "maikg/masqline": "*"
    }
    // ...
}
```

## Query Objects

DBAL provides a `QueryBuilder`, but this class doesn't do any sort of type conversion automatically. All query objects
defined in MaSQLine expect a DBAL database connection, as well as a `\Doctrine\DBAL\Schema\Schema` instance. All
necessary type information is extracted from this schema when building queries. This means that any query conditions as
well as the query results are properly type-converted.

> **NOTE:** You can create the `Schema` instance manually, which I prefer to do, or create one on-the-fly from your
> current database schema by using DBAL's `SchemaManager`. Doing this has severe performance implications, so I suggest
> hardcoding your schema in a file somewhere. You can easily convert `Schema` instances to SQL DDL statements and
> even compare them to another schema version and calculate the DDL statements to migrate between them.
> More documentation [here](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html)
> and [here](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-manager.html).

When building queries, the query objects need to know what types to map the selected columns and condition params to.
Most of the time, this can be inferred from the `Schema` by following a few naming conventions when referring to
columns.

### Usage

Assume the following schema.

```php
<?PHP
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
?>
```

We also need an instance of `\Doctrine\DBAL\Connection`:

```php
<?PHP
$conn = \Doctrine\DBAL\DriverManager::getConnection(
    array(
        'driver'    => 'pdo_sqlite',
        'db_name'   => 'masqline_examples',
        'memory'    => true
    ),
    new \Doctrine\DBAL\Configuration()
);
?>
```

#### SelectQuery

Consider the following `SelectQuery` example:

```php
<?PHP
use MaSQLine\Queries\SelectQuery;

$query = new SelectQuery($conn, $schema);
$sql = $query
    ->select(
        'posts.*',
        // Specify an alias by wrapping in an array.
        array('authors.username' => 'author_username')
    )
    ->from('posts')
    // Shortcut join syntax, which you can read as follows: "[local_table].[foreign_key]
    // maps to [foreign_table].[primary_key]". You can also use leftJoin().
    ->innerJoin('posts.author_id', 'authors.id')
    ->where(function($where) {
        $where
            ->in('posts.author_id', array(2, 3))
            ->orGroup(function($where) {
                $where
                    ->like('posts.title', 'Foo%')
                    ->like('posts.title', '%Bar');
            });
    })
    ->orderBy('-posted_at', 'posts.id')
    ->limit(10)
    ->offset(20)
    ->toSQL();
?>
```

`$sql` now contains the following SQL statement.

```sql
SELECT `posts`.*, `authors`.`username` AS `author_username`
FROM `posts`
INNER JOIN `authors` ON `posts`.`author_id` = `authors`.`id`
WHERE (`posts`.`author_id` IN (?) AND (`posts`.`title` LIKE ? OR `posts`.`title` LIKE ?))
ORDER BY `posted_at` DESC, `posts`.`id` ASC
LIMIT 20,10
```

The placeholder values are set accordingly, and can be extracted from the query through the `getParamValues()` method.
Types can be fetched using `getParamTypes()`. Both of these methods return arrays with their values in the same order
as their corresponding placeholders in the query.

You can also execute query objects directly instead of converting them to an SQL statement.

```php
<?PHP
// Fetch all rows as an array of associative arrays. Database types are automatically
// converted to their PHP counterparts.
$rows = $query->fetchAll();

// Fetch only the first row as an associative array.
$row = $query->fetchOne();

// Fetch only a single (named) column of all results.
$titles = $query->fetchList('title');

// Fetch only the first column of all results.
$firsts = $query->fetchList();

// Fetch only a single named column from the first row.
$title = $query->fetchValue('title');

// Fetch only the the first selected column of the first row.
$first = $query->fetchValue();
?>
```

Some more specialized examples below.

```php
<?PHP
// Specifying custom types and using raw SQL expressions. Raw SQL expressions are left intact by
// the query builder. Custom types can always be defined, in which case they override the inferred
// type. In some cases, like when using raw SQL expressions, an InvalidArgumentException will be
// thrown if no type is specified, since it can not be inferred from the schema.
$query
    // ...
    // When executing this query, the 'id' column in the result will be a string instead of an int.
    ->selectColumn('posts.id', 'string')
    ->having(function($having) {
        // Not a column of the form "[table_name].[column_name]", so specifying a type is mandatory.
        $having->greaterThan(Query::raw('COUNT(*)'), 3, 'integer')
    })
    // ...

// Selecting aggregate columns.
$query
    ->select('posts.author_id')
    
    // Uses the same type as posts.posted_at. You can specify a custom type as the fourth parameter.
    ->selectAggr('MIN', 'posts.posted_at', 'first_posted_at')
    ->selectAggr('MAX', 'posts.posted_at', 'last_posted_at')
    
    // First argument specifies the column to count. If set to NULL, uses COUNT(*).
    ->selectCount(NULL, 'num_posts')
    
    ->from('posts')
    ->groupBy('author_id');

// Complex join conditions.
$query
    // ...
    ->from('posts')
    // Note that you specify the table to join as the first argument using this syntax.
    ->leftJoin('authors', function($conditions) {
        $conditions
            ->equalColumns('posts.author_id', 'authors.id')
            ->in('authors.id', array(2, 3));
    })
    // ...
?>
```

#### Manipulation queries

Besides a way to build select queries, MaSQLine also includes several query classes for data manipulation.
These are `InsertQuery`, `UpdateQuery` and `DeleteQuery`. Check out their sources for API details; they should be
self-explanatory.

You can execute these queries as follows.

```php
<?PHP
$query = ...; // Manipulation query created here
$affected_row_count = $query->execute();
?>
```

#### Wrapper Class

MaSQLine provides a wrapper class to encapsulate the database connection and schema instance. This class also provides
factory methods to create query object instances that automatically inject the database connection and schema into the
query objects' constructors. Moreover, these factory methods provide some syntactic sugar by allowing you to create, set
up and execute the query in one long chain of method calls.

```php
<?PHP
$db = new \MaSQLine\DB($conn, $schema);

$db
    ->createDeleteQuery('posts')
    ->where(function($where) {
        $where->equals('posts.id', 2);
    })
    ->execute();
?>
```

The wrapper class also wraps the `transactional()` method of `\Doctrine\DBAL\Connection`, passing itself to the closure
rather than the connection instance. You can always access the underlying DBAL connection by calling `getConnection()`
on the wrapper class.

## Types

MaSQLine uses the DBAL type system underneath, which is extensible. This allows you to define custom types to use
in the schema definition, and provide rules for type conversion between database and PHP values. Types are explained
in detail [here](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html).

MaSQLine provides several types that are missing from DBAL but are pretty common when designing databases. Currently,
only one type is defined, namely `AbstractEnumType`. Due to the way DBAL's type system is set up, you'll need to create
a separate subclass for every `ENUM` type. This abstract base class makes doing this as easy as possible. The values are
stored as `smallint` values, and converted to their virtual value during type conversion. This strategy means that you
can use any virtual type you want. Just make sure that the `smallint` values keep mapping to the correct virtual values,
otherwise you will get out-of-sync with older data!

## TODO

* Add API documentation.
* Support for subqueries (by nesting query objects).
* Support for platform-specific flags (f.e. INSERT DELAYED for MySQL?)
* Cross-database queries.
* UNION query object (depends on subquery support).
