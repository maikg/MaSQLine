<?PHP
namespace MaSQLine\Queries;

abstract class ManipulationQuery extends Query {
  public function execute() {
    return $this->conn->executeUpdate($this->getFormat(), $this->getValues(), $this->getTypes());
  }
}
