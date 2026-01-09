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
	public const __version = '0.1.7';
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
		$pdo = null
	){
		parent::__construct($whereSql);
		if($pdo){
			$this->pdo = $pdo;
		}
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

	# @ DbSqlInterface
	public function query(string $query = '', array $params = []): DbResultSql
	{
		if (!$query) {
			$query = $this->query = parent::get();
		}

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

	/**
   * 멀티쿼리 (PostgreSQL 전용)
   * - 사용법 1 (기본 1줄): $this->db->multiQuery($sql1, $sql2) 
   * - 사용법 2 (목록): $this->db->multiQuery( [$sql_list, 'all'] )
   * - 사용법 3 (혼합): $this->db->multiQuery( $sql1, [$sql_list, 'all'] )
   * @return array [ 0 => '[JSONString]', 1 => '[JSONString]' ]
   */
  public function multiQuery(...$queries) : array {
    if (empty($queries)) {
      return [];
    }

    $select_parts = [];
    foreach ($queries as $index => $item) {
      if (is_array($item)) {
				$sql = $item[0]; 
      } else {
				$sql = $item;
      }

      $sql = rtrim(trim($sql), ';');

      // 쿼리 조립 (COALESCE로 NULL -> '[]' 처리)
      $select_parts[] = sprintf(
        "COALESCE((SELECT json_agg(t%d.*) FROM (%s) as t%d), '[]') as result_%d",
        $index, $sql, $index, $index
      );
    }

    $final_query = "SELECT " . implode(', ', $select_parts);
    
    try {
      $row = $this->query($final_query)->fetch_assoc();
    } catch (Exception $e) {
      throw new Exception("MultiQuery failed: " . $e->getMessage());
    }

    // 인덱스 배열로 반환
    return $row ? array_values($row) : [];
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

	/**
   * Upsert
   * 키값이 중복되면 -> 나머지 모든 필드를 업데이트
   * 키값이 없으면 -> 새로 입력
   */
  public function upsert(array $conflict_target) : void {
    if (empty($this->params)) {
      throw new Exception("Empty : params for upsert");
    }

    if (empty($conflict_target)) {
      throw new Exception("Empty : conflict_target for upsert");
    }

    $fields = [];
    $placeholders = [];
    $boundParams = [];

    // INSERT 구문 준비
    foreach ($this->params as $field => $value) {
      $fields[] = $field;

      if (is_string($value) && (str_contains($value, 'encode('))) {
        $placeholders[] = $value;
      } else {
        $placeholders[] = ":$field";
        $boundParams[":$field"] = $value;
      }
    }

    // 업데이트할 컬럼 자동 계산 (전체 컬럼 - 키값 컬럼)
    $update_cols = array_diff($fields, $conflict_target);

    // 쿼리 조립
    $conflict_target_str = implode(',', array_map([$this, 'quoteIdentifier'], $conflict_target));
    $do_update_str = "";

    if (empty($update_cols)) {
			// 업데이트할 컬럼이 하나도 없으면 (키값만 넣은 경우) -> 생성만 시도하고 중복시 무시
			$do_update_str = "DO NOTHING";
    } else {
			// 나머지 컬럼은 전부 업데이트 (EXCLUDED 사용)
			$set_parts = [];
			foreach ($update_cols as $col) {
				$quotedCol = $this->quoteIdentifier($col);
				$set_parts[] = "{$quotedCol} = EXCLUDED.{$quotedCol}";
			}
			$do_update_str = "DO UPDATE SET " . implode(', ', $set_parts);
    }

    $query = sprintf(
      "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) %s",
      $this->query_params['table'],
      implode(',', array_map([$this, 'quoteIdentifier'], $fields)),
      implode(',', $placeholders),
      $conflict_target_str,
      $do_update_str
    );

    try {
      $this->params = [];
      $this->query($query, $boundParams);
    } catch (Exception $e) {
      throw new Exception("Upsert failed: " . $e->getMessage());
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
	public function tableJoin(string $join, ...$tables) : self 
	{  
    $upcase = strtoupper($join);
    
    // UNION 인지 확인 (UNION은 이어붙이기 개념이 아니라 템플릿 변경이 필요함)
    $is_union = ($upcase === 'UNION' || $upcase === 'UNION ALL');

    // UNION 처리
    if ($is_union) {
			parent::init('MAIN'); // 초기화
			// 부모 클래스(QueryBuilderAbstractSql)의 오타('UNINON')에 맞춰 키를 전달
			parent::setQueryTpl('UNINON'); 
			
			$join_connector = sprintf(" %s ", $upcase);
			$value = implode($join_connector, $tables);
    } 
    // 일반 JOIN 처리 (INNER, LEFT, RIGHT 등)
    else {
			$join_connector = sprintf(" %s JOIN ", $upcase);
			$new_join_part = implode($join_connector, $tables);
			
			// 현재 설정된 테이블 값을 가져옴
			$current_table = $this->query_params['table'] ?? '';

			if ($current_table) {
				// [CASE A: 신규 방식] table()이 이미 선언된 경우 -> 뒤에 이어 붙임 (Append)
				$value = $current_table . $join_connector . $new_join_part;
			} else {
				// [CASE B: 기존 방식] table() 없이 바로 tableJoin 호출 -> 초기화 후 설정
				parent::init('MAIN');
				parent::setQueryTpl('default');
				$value = $new_join_part;
			}
    }

    // 최종 조립된 문자열을 설정
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