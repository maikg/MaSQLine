<?PHP
namespace MaSQLine\Queries;

abstract class FetchQuery extends Query {
  abstract protected function getConversionTypes();
  
  
  public function fetchAll($key_column = NULL) {
    $data = $this->conn
      ->executeQuery($this->toSQL(), $this->getParamValues(), $this->getParamTypes())
      ->fetchAll(\PDO::FETCH_ASSOC);
    
    foreach ($data as &$row) {
      $row = $this->convertDatabaseValuesToPHPValues($row);
    }
    
    if ($key_column !== NULL) {
      $keys = array_map(function($row) use ($key_column) {
        return $row[$key_column];
      }, $data);
      $data = array_combine($keys, $data);
    }
      
    return $data;
  }
  
  
  public function fetchOne() {
    // Since we're only going to fetch one row, we might as well limit the request.
    $this->limit(1);
    
    $row = $this->conn
      ->executeQuery($this->toSQL(), $this->getParamValues(), $this->getParamTypes())
      ->fetch(\PDO::FETCH_ASSOC);
    
    if ($row === false) {
      return NULL;
    }
    
    $row = $this->convertDatabaseValuesToPHPValues($row);
    
    return $row;
  }
  
  
  public function fetchList($column_name = NULL, $key_column = NULL) {
    return array_map(function($row) use ($column_name) {
      if ($column_name === NULL) {
        $values = array_values($row);
        return $values[0];
      }
      
      return $row[$column_name];
    }, $this->fetchAll($key_column));
  }
  
  
  public function fetchValue($column_name = NULL) {
    $row = $this->fetchOne();
    return ($column_name === NULL) ? current($row) : $row[$column_name];
  }
  
  
  private function convertDatabaseValuesToPHPValues(array $row) {
    $conversion_types = $this->getConversionTypes();
    $converted_row = array();
    foreach ($row as $key => $value) {
      assert('array_key_exists($key, $conversion_types)');
      $type = $conversion_types[$key];
      $converted_row[$key] = $type->convertToPHPValue($value, $this->conn->getDatabasePlatform());
    }
    return $converted_row;
  }
}
