<?PHP
namespace MaSQLine\Queries;

abstract class FetchQuery extends Query {
  private $conversion_types = array();
  
  
  protected function setConversionType($column_alias, $type) {
    $this->conversion_types[$column_alias] = $type;
  }
}
