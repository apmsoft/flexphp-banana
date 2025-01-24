<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Db\DbResultCouch;
use Flex\Banana\Classes\Db\DbInterface;
use Flex\Banana\Classes\Http\HttpRequest;
use Flex\Banana\Classes\Array\ArrayHelper;
use Flex\Banana\Classes\Date\DateTimez;
use \Exception;
use \ArrayAccess;

class DbCouch extends QueryBuilderAbstractCouch implements DbInterface, ArrayAccess
{
    public const __version = '0.3.1';
    private const BASE_URL = "http://{host}:{port}";

    public string $baseUrl;
    private string $authHeader;
    private string $database;
    private array $params = [];
    private array $executeQueries = [];
    private string $table = '';

    public function __construct(
        WhereCouch $whereCouch
    )
    {
        parent::__construct($whereCouch);
        $this->baseUrl = '';
        $this->authHeader = '';
        $this->database = '';
    }

    # @ DbSqlInterface
    public function connect(string $host, string $dbname, string $user, string $password, int $port, string $charset, ?array $options = []): self
    {
        $this->baseUrl = $this->bindingDNS(self::BASE_URL, [
            "host" => $host,
            "port" => $port
        ]);

        $this->authHeader = "Authorization: Basic " . base64_encode("$user:$password");
        $httpRequest = new HttpRequest();
        $httpRequest->set($this->baseUrl, "", [$this->authHeader,"Content-Type: application/json"]);
        $httpRequest->get(function($response) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Failed to connect to CouchDB server");
            }
        });

    return $this->selectDB($dbname);
    }

    # @ DbSqlInterface
    public function selectDB(string $dbname): self
    {
        $httpRequest = new HttpRequest();
        $dbUrl = $this->baseUrl . "/$dbname";
        if($this->database != $dbname){
            $httpRequest->set($dbUrl, "", [$this->authHeader,"Content-Type: application/json"]);
            $httpRequest->get(function($response) use ($dbname) {
                if (empty($response) || isset($response[0]['error'])) {
                    throw new Exception("Failed to connect to database '$dbname'");
                }
            });
            $this->database = $dbname;
        }
        return $this;
    }

    # @ DbSqlInterface
    public function whereHelper(): WhereCouch
    {
        return $this->whereCouch;
    }


    # @ DbSqlInterface
    public function query(string $query = '', array $params = []): DbResultCouch | array
    {
        if (!$query) {
            $query = JsonEncoder::toJson([$this->get()]);
        }

        try{
            $httpRequest = new HttpRequest();
            $url = ($this->table) ? $this->baseUrl."/{$this->database}/_partition/{$this->table}/_find" : $this->baseUrl."/{$this->database}/_find";
            $params = json_decode($query,true);
            foreach($params as $param){
                $httpRequest->set($url, JsonEncoder::toJson($param), [$this->authHeader,'Content-Type: application/json']);
            }

            $result = [];
            $httpRequest->post(function($response) use (&$result) {
                foreach($response as $body){
                    if (empty($body) || isset($body['error'])) {
                        throw new Exception("Query failed: " . ($body['error'] ?? 'Unknown error'));
                    }
                    $result[] = new DbResultCouch($body);
                }
            });

            if(count($result)==1){
                $result = $result[0];
            }
            return $result;
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    # @ DbSqlInterface
    public function insert(): void
    {
        if (empty($this->params)) {
            throw new Exception("Empty params");
        }

        if(!isset($this->params['_id']) && $this->table){
            $this->params['_id'] = $this->table.':'.$this->generate_id();
        }

        $this->executeQueries[] = [
            "params"  => $this->params
        ];

        parent::init();
        $this->params = [];
    }

    private function generate_id() : string {
        $now = (new DateTimez("now"))->format('YmdHis');
        $microtodate = $now . substr((string)microtime(), 2, 6);
        $uniqid = substr(uniqid(rand(), true), 0, 6); // 고유한 짧은 문자열
        return $microtodate.'-'.$uniqid;
    }

    # @ DbSqlInterface
    public function update(): void
    {
        if (empty($this->params)) {
            throw new Exception("Empty parameters or selector is missing");
        }

        # where 문에서 _id 값 찾기
        if(!isset($this->params['_id']))
        {
            $selectors = (isset($this->query_params['selector']['$and'])) ? $this->query_params['selector']['$and']: $this->query_params['selector'];
            $wheres = (new ArrayHelper($selectors))->select("_id")->value;
            if(!isset($wheres[0]) || !isset($wheres[0]['_id'])){
                throw new Exception("Empty _id is missing");
            }

            $_id = array_values($wheres[0]['_id'])[0];
            $this->params['_id'] = $_id;
        }

        # _id 값만 추출하기 및 fields 등록
        if(!isset($this->params['_id'])){
            throw new Exception("Empty _id value is missing");
        }

        # _rev 가 있는지 체크
        if(!isset($this->params['_rev'])){
            throw new Exception("Empty _rev is missing");
        }

        $this->executeQueries[] = [
            "params"  => $this->params
        ];

        parent::init();
        $this->params = [];
    }

    # @ DbSqlInterface
    public function delete(): void
    {
        # where 문에서 _id 값 찾기
        if(!isset($this->params['_id'])){
            $selectors = (isset($this->query_params['selector']['$and'])) ? $this->query_params['selector']['$and']: $this->query_params['selector'];
            $wheres = (new ArrayHelper($selectors))->select("_id")->value;
            if(!isset($wheres[0]) || !isset($wheres[0]['_id'])){
                throw new Exception("Empty _id is missing");
            }

            $_id = array_values($wheres[0]['_id'])[0];
            $this->params['_id'] = $_id;
        }

        # _id 값만 추출하기 및 fields 등록
        if(!isset($this->params['_id'])){
            throw new Exception("Empty _id value is missing");
        }

        # _rev 가 있는지 체크
        if(!isset($this->params['_rev'])){
            throw new Exception("Empty _rev is missing");
        }
        $this->params['_deleted'] = true;

        $this->executeQueries[] = [
            "params"  => $this->params
        ];

        parent::init();
        $this->params = [];
    }

    public function createDatabase(string $dbname): void
    {
        try {
            $httpRequest = new HttpRequest();
            $dbUrl = $this->baseUrl . "/$dbname";
            $httpRequest->set($dbUrl, "", [$this->authHeader,"Content-Type: application/json"]);
            $httpRequest->put(function($response) use ($dbname) {
                if (empty($response) || isset($response[0]['error'])) {
                    throw new Exception("Failed to create database '$dbname'");
                }
            });
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    # @ QueryBuilderAbstractCouch
    public function total(string $column_name = '_id'): int
    {
        $this->set('fields', [$column_name]);
        $this->set('limit', 1);
        $query = $this->get();
        $query['execution_stats'] = true;

        $result = $this->query(JsonEncoder::toJson([$query]));

        if ($result instanceof DbResultCouch) {
            $executionStats = $result->get_execution_stats();
            if (is_array($executionStats) && isset($executionStats['total_docs'])) {
                return (int)$executionStats['total_docs'];
            }
        }

        // 실행 통계를 얻지 못한 경우, 전체 문서를 가져와서 카운트
        $this->init(); // 쿼리 파라미터 초기화
        $this->set('fields', ['_id']);
        $allDocsResult = $this->query(JsonEncoder::toJson([$this->get()]));

        return $allDocsResult->num_rows();
    }

    # @ QueryBuilderAbstractCouch
    public function table(...$tables): self
    {
        if(empty($tables[0])){
            throw new Exception("Empty table(type) is missing");
        }

        parent::init();
        $this->table = $tables[0];
        return $this;
    }

    # @ QueryBuilderAbstractCouch
    public function select(...$columns) : self{
		if(count($columns) == 1){
            if(strpos($columns[0],",") !==false) {
                $columns = explode(",", $columns[0]);
            }
        }
        if($columns[0] != '*'){
            if(!in_array('_rev',$columns)){
                $columns[] = '_rev';
            }
            $this->set('fields', $columns);
        }
	return $this;
	}

    # @ QueryBuilderAbstractCouch
    public function where(...$where): self
    {
        $result = null;
        if(isset($where[0]) && $where[0]){
            $result = (!isset($where[1])) ? $where[0] : $this->buildWhere($where);
        }
		if($result !==null && $result){
            $this->set('selector', $result);
		}
    return $this;
    }

    # @ QueryBuilderAbstractCouch
    public function orderBy(...$orderby): self
    {
        $sort = [];
        foreach ($orderby as $field) {
            $direction = 'asc';
            $field = strtolower($field);
            if (strpos($field, ' desc') !== false) {
                $field = str_replace(' desc', '', $field);
                $direction = 'desc';
            }
            $sort[] = [$field => $direction];
        }
        $this->set('sort', $sort);
        return $this;
    }

    # @ QueryBuilderAbstractCouch
    public function limit(...$limit): self
    {
        if (isset($limit[1])) {
            $this->set('skip', $limit[0]);
            $this->set('limit', $limit[1]);
        } else {
            $this->set('limit', $limit[0]);
        }
        return $this;
    }

    # @ QueryBuilderAbstractCouch
    public function useIndex(...$index): self
    {
        $this->set('use_index', $index);
        return $this;
    }

    public function beginTransaction(): void
    {
        parent::init();
        $this->params = [];
        $this->executeQueries = [];
    }

    public function commit(): mixed
    {
        $result = null;
        $executeQueries = $this->executeQueries;

        parent::init();
        $this->params = [];
        $this->executeQueries = [];

        $httpRequest = new HttpRequest();
        try {
            $bulkDocs = ['docs' => array_map(fn($query) => $query['params'], $executeQueries)];
            // $url = ($this->table) ? $this->baseUrl."/{$this->database}/_partition/{$this->table}/_bulk_docs" : $this->baseUrl."/{$this->database}/_bulk_docs";
            $url = $this->baseUrl."/{$this->database}/_bulk_docs";
            $params = JsonEncoder::toJson($bulkDocs);

            $httpRequest->set($url, $params, [$this->authHeader, "Content-Type: application/json"]);
            $result = $httpRequest->post(function($response) {
                foreach ($response as $body) {
                    if (empty($body) || isset($body['error'])) {
                        throw new Exception("Bulk operation failed: " . ($body['error'] ?? 'Unknown error'));
                    }
                }
                return true;
            });
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $result;
    }

    public function rollBack(): void
    {
        parent::init();
        $this->params = [];
        $this->executeQueries = [];
    }

    public function offsetSet($offset, $value): void
    {
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
    }

    public function __get(string $propertyName)
    {
        if ($propertyName == 'query') {
            return $this->get();
        } else {
            return $this->{$propertyName} ?? null;
        }
    }
}