<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\QueryBuilderAbstractSql;
use Flex\Banana\Classes\Db\DbResultSql;
use Flex\Banana\Classes\Db\DbInterface;
use \PDO;
use \PDOException;
use \Exception;
use \ArrayAccess;

class DbPgSql extends QueryBuilderAbstractSql implements DbInterface,ArrayAccess
{
	public const __version = '0.1.5';
	private const DSN = "pgsql:host={host};port={port};dbname={dbname}";

    public $pdo;
    private $params = [];
		private array $bulkData = []; // bulk 데이터를 저장할 배열
    private array $pdo_options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

	public function __construct(
		WhereSql $whereSql,
		?PDO $pdo = null
	){
		parent::__construct($whereSql);
	}

	# @ DbSqlInterface
    public function connect(string $host, string $dbname, string $user, string $password, int $port, string $charset, ?array $options=[]) : self
	{
		try {
			$dsn = $this->bindingDNS(self::DSN, [
				"host"    => $host,
				"dbname"  => $dbname,
				"port"    => $port,
				"charset" => $charset
			]);
			$this->pdo = new PDO($dsn, $user, $password, $this->pdo_options+$options);
		} catch (PDOException $e) {
			throw new Exception($e->getMessage());
		}

		return $this->selectDB( $dbname );
	}

	# @ DbSqlInterface
	public function selectDB( string $dbname ): self
	{
		$query = "SELECT current_database()";
		$result = $this->pdo->query($query)->fetchColumn();
		if ($result !== $dbname) {
			throw new Exception("Connected to database '$result' instead of '$dbname'");
		}
	return $this;
	}

	# @ DbSqlInterface
	public function whereHelper(): WhereSql
	{
			return $this->whereSql;
	}

	# pdo 선언 여부 체크
	private function ensurePdo(): void {
    if (!$this->pdo instanceof \PDO) {
        throw new \Exception('PDO is not set. Inject PDO or call connect() first.');
    }
	}

	# @ DbSqlInterface
	public function query(string $query = '', array $params = []): DbResultSql
	{
		$this->ensurePdo();
		if (!$query) {
			$query = $this->query = parent::get();
		}

		// echo "Executing query: " . $query . PHP_EOL;
		// print_r($params);

		try {
			$stmt = $this->pdo->prepare($query);
			$result = $stmt->execute($params ?: null);
			if (!$result) {
				throw new Exception("Execution failed: " . implode(", ", $stmt->errorInfo()));
			}

			return new DbResultSql($stmt);
		} catch (PDOException $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	protected function quoteIdentifier($identifier): string
	{
		return '"' . str_replace('"', '""', $identifier) . '"';
	}

	# @ DbSqlInterface
	public function insert() : void {
		if (empty($this->params)) {
			throw new Exception("Empty : params");
		}

		$fields = [];
		$placeholders = [];
		$boundParams = [];

		foreach ($this->params as $field => $value) {
			$fields[] = $field;

			// Check for HEX(AES_ENCRYPT and encode(encrypt_iv
			if (is_string($value) && (str_contains($value, 'encode('))) {
				$placeholders[] = $value; // Directly add the expression to placeholders
			} else {
				$placeholders[] = ":$field";
				$boundParams[":$field"] = $value;
			}
		}

		$query = sprintf(
			"INSERT INTO %s (%s) VALUES (%s)",
			$this->query_params['table'],
			implode(',', $fields),
			implode(',', $placeholders)
		);

		try {
			$this->params = [];
			$this->query($query, $boundParams);
		} catch (Exception $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	# db[] 용을 bulk 배열에 담는 기능
	public function bulk() : self
	{
		$this->bulkData[] = $this->params;
		$this->params = [];
		
		return $this;
	}

	// 벌크 데이터 삽입 메소드
	public function insertBulk() : void
	{
		if (empty($this->bulkData)) {
			throw new \Exception("No data to insert in bulk.");
		}

		// 첫 번째 데이터 행을 기반으로 컬럼 이름 추출
		$firstRow = reset($this->bulkData);
		$fields   = array_keys($firstRow);

		// SQL 쿼리 생성
		$values = [];
		$boundParams  = [];
		$i = 0;
		foreach ($this->bulkData as $row) {
			$placeholders = [];
			foreach ($fields as $field) {
				$placeholder               = ":{$field}_{$i}";
				$placeholders[]            = $placeholder;
				$boundParams[$placeholder] = $row[$field];
			}
			$values[] = sprintf("(%s)", implode(', ', $placeholders));
			$i++;
		}

		$query = sprintf(
			"INSERT INTO %s (%s) VALUES %s",
			$this->query_params['table'],
			implode(', ', $fields),
			implode(', ', $values)
		);

		try {
			$this->bulkData = []; // 삽입 후 데이터 초기화
			$this->query($query, $boundParams);
		} catch (Exception $e) {
			throw new Exception("Bulk insert failed: " . $e->getMessage());
		}
	}

	# @ DbSqlInterface
	public function update() : void {
		if (empty($this->params) || empty($this->query_params['where'])) {
			throw new Exception("Empty parameters or WHERE clause is missing");
		}

		$setClauses = [];
		$boundParams = [];

		foreach ($this->params as $field => $value) {
			if (is_string($value) && str_contains($value, 'encode(')) {
				$setClauses[] = "$field = $value";
			}else {
				$setClauses[] = "$field = :$field";
				$boundParams[":$field"] = $value;
			}
		}

		$query = sprintf(
			"UPDATE %s SET %s %s",
			$this->query_params['table'],
			implode(',', $setClauses),
			$this->query_params['where']
		);

		try {
			$this->params = [];
			$this->query($query, $boundParams);
		} catch (Exception $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	public function updateBulk() : void
  {
    if (empty($this->bulkData)) {
      throw new \Exception("No data to update in bulk.");
    }

		// WHERE 절이 없는 경우 예외 처리
		if (empty($this->query_params['where'])) {
			throw new \Exception("WHERE clause is missing for bulk update.");
		}
    
    // 이 부분은 $this->params를 사용하지 않으므로, $this->bulkData를 직접 사용합니다.
    $firstRow = reset($this->bulkData);
    $fields = array_keys($firstRow);
    
    $values_parts = [];
    $set_clauses = [];
    $boundParams = [];
    $i = 0;
    
    foreach ($this->bulkData as $row) {
        $placeholders = [];
        foreach ($fields as $field) {
            $placeholder = ":{$field}_{$i}";
            $placeholders[] = $placeholder;
            $boundParams[$placeholder] = $row[$field];
            
            // UPDATE SET 절에 사용될 필드
            $set_clauses[$field] = sprintf("%s = temp.%s", $this->quoteIdentifier($field), $this->quoteIdentifier($field));
        }
        $values_parts[] = sprintf("(%s)", implode(', ', $placeholders));
        $i++;
    }
    
    $query = sprintf(
      "UPDATE %s AS t SET %s FROM (VALUES %s) AS temp(%s) %s",
      $this->query_params['table'],
      implode(', ', array_unique(array_values($set_clauses))),
      implode(', ', $values_parts),
      implode(', ', array_map([$this, 'quoteIdentifier'], $fields)),
      $this->query_params['where']
    );
    
    try {
      $this->bulkData = []; // 데이터 초기화
      $this->query($query, $boundParams);
    } catch (\Exception $e) {
      throw new \Exception("Bulk update failed: " . $e->getMessage());
    }
  }

	# @ DbSqlInterface
	public function delete() : void {
		$query = sprintf("DELETE FROM %s %s",
			$this->query_params['table'],
			$this->query_params['where']
		);
		try {
			$this->query($query);
		} catch (Exception $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	# @ QueryBuilderAbstract
	public function tableJoin(string $join, ...$tables) : self{
		parent::init('JOIN');

		$upcase = strtoupper($join);
		$implode_join = sprintf(" %s JOIN ",$upcase);
		parent::setQueryTpl('default');

		$value = implode($implode_join, $tables);
		parent::set('table', $value);
	return $this;
	}

	# @ QueryBuilderAbstract
    public function select(...$columns) : self{
		$value = implode(',', $columns);
		parent::set('columns', $value);
	return $this;
	}

	# @ QueryBuilderAbstract
    public function where(...$where) : self
	{
		$result = parent::buildWhere($where);
		if($result){
			$value = 'WHERE '.$result;
			parent::set('where', $value);
		}
	return $this;
	}

	# @ QueryBuilderAbstract
    public function orderBy(...$orderby) : self
	{
		$value = 'ORDER BY '.implode(',',$orderby);
		parent::set('orderby', $value);
	return $this;
	}

	# @ QueryBuilderAbstract
    public function on(...$on) : self
	{
		$result = parent::buildWhere($on);
		if($result){
			$value = 'ON '.$result;
			parent::set('on', $value);
		}
	return $this;
	}

	# @ QueryBuilderAbstract
	public function limit(...$limit): self {
		$value = match (count($limit)) {
			1 => 'LIMIT ' . $limit[0],
			2 => 'LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0],
			default => throw new Exception("Invalid number of arguments for LIMIT clause")
		};

		parent::set('limit', $value);
		return $this;
	}

	# @ QueryBuilderAbstract
    public function distinct(string $column_name) : self{
		$value = sprintf("DISTINCT %s", $column_name);
		parent::set('columns', $value);
	return $this;
	}

	# @ QueryBuilderAbstract
    public function groupBy(...$columns) : self{
		$value = 'GROUP BY '.implode(',',$columns);
		parent::set('groupby', $value);
	return $this;
	}

	# @ QueryBuilderAbstract
    public function having(...$having) : self{
		$result = parent::buildWhere($having);
		if($result){
			$value = 'HAVING '.$result;
			parent::set('having', $value);
		}
	return $this;
	}

	# @ QueryBuilderAbstract
	public function total(string $column_name = '*') : int {
		$value = sprintf("COUNT(%s) AS total_count", $column_name);
		parent::set('columns', $value);
		$query = parent::get();

		$result = $this->query($query);
		$row = $result->fetch_assoc();
		return (int)($row['total_count'] ?? 0);
	}

	# @ QueryBuilderAbstract
	public function table(...$tables) : self {
		parent::init('MAIN');
		$length = count($tables);
		$value = ($length == 2) ? $tables[0] . ',' . $tables[1] : $tables[0];
		parent::set('table', $value);
		return $this;
	}

	# @ QueryBuilderAbstract
	public function tableSub(...$tables) : self{
		parent::init('SUB');
		$length = count($tables);
		$value = ($length ==2) ? implode(',',$tables) : implode(' ',$tables);
		parent::set('table', $value);
	return $this;
	}

	# @ ArrayAccess
	# 사용법 : $obj["two"] = "A value";
	public function offsetSet($offset, $value) : void {
		$this->params[$offset] = $value;
	}

	# @ ArrayAccess
	# 사용법 : isset($obj["two"]); -> bool(true)
	public function offsetExists($offset) : bool{
		return isset($this->params[$offset]);
	}

	# @ ArrayAccess
	# 사용법 : unset($obj["two"]); -> bool(false)
	public function offsetUnset($offset) : void{
		unset($this->params[$offset]);
	}

	# @ ArrayAccess
	# 사용법 : $obj["two"]; -> string(7) "A value"
	public function offsetGet($offset) : mixed{
		return isset($this->params[$offset]) ? $this->params[$offset] : null;
	}

    public function __call($method, $args)
    {
		return call_user_func_array([$this->pdo, $method], $args);
    }

	public function __get(string $propertyName) {
		if(property_exists(__CLASS__,$propertyName)){
			if($propertyName == 'query'){
				return parent::get();
			}else{
				return $this->{$propertyName};
			}
		}
	}
}