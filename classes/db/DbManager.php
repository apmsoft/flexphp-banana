<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\QueryBuilderAbstract;
use Flex\Banana\Classes\Db\DbSqlResult;
use \PDO;
use \PDOException;
use \Exception;
use \ArrayAccess;

class DbManager extends QueryBuilderAbstract implements DbSqlInterface,ArrayAccess
{
	public const __version = '0.1.2';
    public $pdo;
    private $params = [];
    private array $pdo_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    public function __construct(string $db_type){
        parent::__construct($db_type);
    }

    public function connect(string $host, string $dbname, string $user, string $password, int $port, string $charset, ?array $options=[]) : self
	{
		$dsn = $this->createDSN($host, $dbname, $port, $charset);
		echo $dsn.PHP_EOL;
		try {
			$this->pdo = new PDO($dsn, $user, $password, $this->pdo_options+$options);
		} catch (PDOException $e) {
			throw new Exception($e->getMessage());
		}

		// Verify database selection
		$query = $this->selectDB();
		$result = $this->pdo->query($query)->fetchColumn();
		if ($result !== $dbname) {
			throw new Exception("Connected to database '$result' instead of '$dbname'");
		}

		return $this;
	}

	protected function selectDB(): string
	{
		return match($this->db_type) {
			'mysql' => "SELECT DATABASE()",
			'pgsql' => "SELECT current_database()",
			default => throw new Exception("Unsupported database type: " . $this->db_type),
		};
	}

    #@ interface : ArrayAccess
	# 사용법 : $obj["two"] = "A value";
	public function offsetSet($offset, $value) : void {
		$this->params[$offset] = $value;
	}

	#@ interface : ArrayAccess
	# 사용법 : isset($obj["two"]); -> bool(true)
	public function offsetExists($offset) : bool{
		return isset($this->params[$offset]);
	}

	#@ interface : ArrayAccess
	# 사용법 : unset($obj["two"]); -> bool(false)
	public function offsetUnset($offset) : void{
		unset($this->params[$offset]);
	}

	#@ interface : ArrayAccess
	# 사용법 : $obj["two"]; -> string(7) "A value"
	public function offsetGet($offset) : mixed{
		return isset($this->params[$offset]) ? $this->params[$offset] : null;
	}

    # @ abstract : QueryBuilderAbstract
	public function table(...$tables) : DbManager {
		parent::init('MAIN');
		$length = count($tables);
		$value = ($length == 2) ? $tables[0] . ',' . $tables[1] : $tables[0];
		parent::set('table', $value);
		return $this;
	}

	# @ abstract : QueryBuilderAbstract
	public function tableSub(...$tables) : DbManager{
		parent::init('SUB');
		$length = count($tables);
		$value = ($length ==2) ? implode(',',$tables) : implode(' ',$tables);
		parent::set('table', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
	public function tableJoin(string $join, ...$tables) : DbManager{
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

	# @ abstract : QueryBuilderAbstract
    public function select(...$columns) : DbManager{
		$value = implode(',', $columns);
		parent::set('columns', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function where(...$where) : DbManager
	{
		$result = parent::buildWhere($where);
		if($result){
			$value = 'WHERE '.$result;
			parent::set('where', $value);
		}
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function orderBy(...$orderby) : DbManager
	{
		$value = 'ORDER BY '.implode(',',$orderby);
		parent::set('orderby', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function on(...$on) : DbManager
	{
		$result = parent::buildWhere($on);
		if($result){
			$value = 'ON '.$result;
			parent::set('on', $value);
		}
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    # @ abstract : QueryBuilderAbstract
	public function limit(...$limit): DbManager {
		$value = match ($this->db_type) {
			'mysql' => 'LIMIT ' . implode(',', $limit),
			'pgsql' => match (count($limit)) {
				1 => 'LIMIT ' . $limit[0],
				2 => 'LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0],
				default => throw new Exception("Invalid number of arguments for LIMIT clause"),
			},
			default => throw new Exception("Unsupported database type for LIMIT clause: {$this->db_type}"),
		};

		parent::set('limit', $value);
		return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function distinct(string $column_name) : DbManager{
		$value = sprintf("DISTINCT %s", $column_name);
		parent::set('columns', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function groupBy(...$columns) : DbManager{
		$value = 'GROUP BY '.implode(',',$columns);
		parent::set('groupby', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function having(...$having) : DbManager{
		$result = parent::buildWhere($having);
		if($result){
			$value = 'HAVING '.$result;
			parent::set('having', $value);
		}
	return $this;
	}

	# @ interface : DBSwitch
	public function query(string $query = '', array $params = []): DbSqlResult
	{
		if (!$query) {
			$query = $this->query = parent::get();
		}

		echo "Executing query: " . $query . PHP_EOL;
		print_r($params);

		try {
			$stmt = $this->pdo->prepare($query);

			// Execute with params if they exist, otherwise pass null
			$result = $stmt->execute($params ?: null);

			// Check if execution was successful
			if (!$result) {
				throw new Exception("Execution failed: " . implode(", ", $stmt->errorInfo()));
			}

			return new DbSqlResult($stmt);
		} catch (PDOException $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	# @ abstract : QueryBuilderAbstract
	public function total(string $column_name = '*') : int {
		$value = sprintf("COUNT(%s) AS total_count", $column_name);
		parent::set('columns', $value);
		$query = parent::get();

		$result = $this->query($query);
		$row = $result->fetch_assoc();
		return (int)($row['total_count'] ?? 0);
	}

	# @ interface : DBSwitch
	# $db['name'] = 1, $db['age'] = 2;
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
			if (is_string($value) && (str_contains($value, 'HEX(AES_ENCRYPT(') || str_contains($value, 'encode('))) {
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
			$result = $this->query($query, $boundParams);
			$this->params = [];
		} catch (Exception $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	# @ interface
	public function update() : void {
		if (empty($this->params) || empty($this->query_params['where'])) {
			throw new Exception("Empty parameters or WHERE clause is missing");
		}

		$setClauses = [];
		$boundParams = [];

		foreach ($this->params as $field => $value) {
			if (is_string($value) && (str_contains($value, 'HEX(AES_ENCRYPT(') || str_contains($value, 'encode('))) {
				$setClauses[] = "$field = $value";
			}else if (is_string($value) && preg_match("/^$field(\+|\-|\*|\/)[0-9]+$/", $value)) {
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
			$result = $this->query($query, $boundParams);
			$this->params = []; // Reset params after update
		} catch (Exception $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	# @ interface : DBSwitch
	public function delete() : void {
		if (empty($this->query_params['where'])) {
			return;
		}

		$query = sprintf("DELETE FROM %s %s", 
			$this->query_params['table'], 
			$this->query_params['where']
		);
		try {
			$result = $this->query($query);
		} catch (Exception $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}


    public function __call($method, $args)
    {
		if(method_exists($this, $method)){
			if($method == 'aes_decrypt' || $method == 'aes_encrypt'){
				return call_user_func_array([$this, $method],$args);
			}
		}else{
			return call_user_func_array([$this->pdo, $method], $args);
		}
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

	# db close
	public function __destruct(){
		// parent::close();
	}
}