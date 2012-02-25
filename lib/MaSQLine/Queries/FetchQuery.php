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
