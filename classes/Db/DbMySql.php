<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\QueryBuilderAbstractSql;
use Flex\Banana\Classes\Db\DbResultSql;
use Flex\Banana\Classes\Db\DbInterface;
use \PDO;
use \PDOException;
use \Exception;
use \ArrayAccess;

class DbMySql extends QueryBuilderAbstractSql implements DbInterface,ArrayAccess
{
	public const __version = '0.1.3';
	private const DSN = "mysql:host={host};port={port};dbname={dbname};charset={charset}";
    public $pdo;
    private $params = [];
    private array $pdo_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

	public function __construct(
		WhereSql $whereSql
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
		$query ="SELECT DATABASE()";
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

	# @ DbSqlInterface
	public function query(string $query = '', array $params = []): DbResultSql
	{
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
        return '`' . str_replace('`', '``', $identifier) . '`';
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
			if (is_string($value) && str_contains($value, 'HEX(AES_ENCRYPT(')) {
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

	# @ DbSqlInterface
	public function update() : void {
		if (empty($this->params) || empty($this->query_params['where'])) {
			throw new Exception("Empty parameters or WHERE clause is missing");
		}

		$setClauses = [];
		$boundParams = [];

		foreach ($this->params as $field => $value) {
			if (is_string($value) && str_contains($value, 'HEX(AES_ENCRYPT(')) {
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

	# @ QueryBuilderAbstractSql
	public function tableJoin(string $join, ...$tables) : self{
		parent::init('JOIN');

		$upcase = strtoupper($join);
		$implode_join = sprintf(" %s JOIN ",$upcase);
		switch($upcase){
			case 'UNION': # 중복제거
			case 'UNION ALL': # 중복포함
				parent::setQueryTpl('UNINON');
				$implode_join = sprintf(" %s ",$upcase);
				break;
			default :
				parent::setQueryTpl('default');
		}

		$value = implode($implode_join, $tables);
		parent::set('table', $value);
	return $this;
	}

	# @ QueryBuilderAbstractSql
    public function select(...$columns) : self{
		$value = implode(',', $columns);
		parent::set('columns', $value);
	return $this;
	}

	# @ QueryBuilderAbstractSql
    public function where(...$where) : self
	{
		$result = parent::buildWhere($where);
		if($result){
			$value = 'WHERE '.$result;
			parent::set('where', $value);
		}
	return $this;
	}

	# @ QueryBuilderAbstractSql
    public function orderBy(...$orderby) : self
	{
		$value = 'ORDER BY '.implode(',',$orderby);
		parent::set('orderby', $value);
	return $this;
	}

	# @ QueryBuilderAbstractSql
    public function on(...$on) : self
	{
		$result = parent::buildWhere($on);
		if($result){
			$value = 'ON '.$result;
			parent::set('on', $value);
		}
	return $this;
	}

	# @ QueryBuilderAbstractSql
	public function limit(...$limit): self {
		$value = 'LIMIT ' . implode(',', $limit);
		parent::set('limit', $value);
		return $this;
	}

	# @ QueryBuilderAbstractSql
    public function distinct(string $column_name) : self{
		$value = sprintf("DISTINCT %s", $column_name);
		parent::set('columns', $value);
	return $this;
	}

	# @ QueryBuilderAbstractSql
    public function groupBy(...$columns) : self{
		$value = 'GROUP BY '.implode(',',$columns);
		parent::set('groupby', $value);
	return $this;
	}

	# @ QueryBuilderAbstractSql
    public function having(...$having) : self{
		$result = parent::buildWhere($having);
		if($result){
			$value = 'HAVING '.$result;
			parent::set('having', $value);
		}
	return $this;
	}

	# @ QueryBuilderAbstractSql
	public function total(string $column_name = '*') : int {
		$value = sprintf("COUNT(%s) AS total_count", $column_name);
		parent::set('columns', $value);
		$query = parent::get();

		$result = $this->query($query);
		$row = $result->fetch_assoc();
		return (int)($row['total_count'] ?? 0);
	}

	# @ QueryBuilderAbstractSql
	public function table(...$tables) : self {
		parent::init('MAIN');
		$length = count($tables);
		$value = ($length == 2) ? $tables[0] . ',' . $tables[1] : $tables[0];
		parent::set('table', $value);
		return $this;
	}

	# @ QueryBuilderAbstractSql
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