<?PHP
namespace MaSQLine\Queries;

abstract class ManipulationQuery extends Query {
  public function execute() {
    return $this->conn->executeUpdate($this->toSQL(), $this->getParamValues(), $this->getParamTypes());
  }
}
