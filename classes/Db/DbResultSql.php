<?php
namespace Flex\Banana\Classes\Db;

use PDO;
use PDOStatement;

class DbResultSql {
    private $statement;
    private ?array $resultSet = null;
    private $currentRow;
    private int $numRows = 0;

    /**
     * @param \PDOStatement|\Swoole\Database\PDOStatementProxy|object $statement
     */
    public function __construct($statement)
    {
        // Swoole 클래스를 import하지 않고 문자열로만 판별 (Swoole 미설치 환경에서도 안전)
        $isPdo    = $statement instanceof \PDOStatement;
        $isSwoole = is_a($statement, 'Swoole\Database\PDOStatementProxy', false);

        // ✨ 메서드 존재 체크는 제거 (프록시가 __call 로 위임하므로 method_exists가 실패함)
        if (!$isPdo && !$isSwoole) {
            $t = is_object($statement) ? get_class($statement) : gettype($statement);
            throw new \InvalidArgumentException('Unsupported statement: ' . $t);
        }

        $this->statement = $statement;

        // 드라이버마다 rowCount()가 예외/0일 수 있으니 안전하게
        try {
            $this->numRows = (int) $this->statement->rowCount();
        } catch (\Throwable $e) {
            $this->numRows = 0;
        }
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