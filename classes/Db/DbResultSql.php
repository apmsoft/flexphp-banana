<?php
namespace Flex\Banana\Classes\Db;

use PDO;
use PDOStatement;

class DbResultSql {
    private $statement;
    private $resultSet;
    private $currentRow;
    private $numRows;

    public function __construct(PDOStatement $statement) {
        $this->statement = $statement;
        $this->resultSet = null;
        $this->currentRow = 0;
        $this->numRows = $statement->rowCount();
    }

    public function fetch_assoc() {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    public function fetch_array($resultType = PDO::FETCH_BOTH) {
        return $this->statement->fetch($resultType);
    }

    public function fetch_row() {
        return $this->statement->fetch(PDO::FETCH_NUM);
    }

    public function fetch_object() {
        return $this->statement->fetch(PDO::FETCH_OBJ);
    }

    public function num_rows() {
        return $this->numRows;
    }

    public function fetch_all($resultType = PDO::FETCH_ASSOC) {
        if ($this->resultSet === null) {
            $this->resultSet = $this->statement->fetchAll($resultType);
        }
        return $this->resultSet;
    }

    public function fetch_column($column = 0) {
        return $this->statement->fetchColumn($column);
    }
}