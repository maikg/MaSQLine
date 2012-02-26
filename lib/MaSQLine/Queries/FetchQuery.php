<?PHP
namespace MaSQLine\Queries;

abstract class FetchQuery extends Query {
  abstract protected function getConversionTypes();
  
  
  public function fetchAll() {
    $data = $this->conn
      ->executeQuery($this->toSQL(), $this->getParamValues(), $this->getParamTypes())
      ->fetchAll(\PDO::FETCH_ASSOC);
    
    foreach ($data as &$row) {
      $row = $this->convertDatabaseValuesToPHPValues($row);
    }
      
    return $data;
  }
  
  
  public function fetchOne() {
    $row = $this->conn
      ->executeQuery($this->toSQL(), $this->getParamValues(), $this->getParamTypes())
      ->fetch(\PDO::FETCH_ASSOC);
    
    if ($row === false) {
      return NULL;
    }
    
    $row = $this->convertDatabaseValuesToPHPValues($row);
    
    return $row;
  }
  
  
  public function fetchList($column_name = NULL) {
    return array_map(function($row) use ($column_name) {
      if ($column_name === NULL) {
        $values = array_values($row);
        return $values[0];
      }
      
      return $row[$column_name];
    }, $this->fetchAll());
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
