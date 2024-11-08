<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Db\DbResultCouch;
use Flex\Banana\Classes\Db\DbInterface;
use Flex\Banana\Classes\Http\HttpRequest;
use Flex\Banana\Classes\Array\ArrayHelper;
use \Exception;
use \ArrayAccess;

class DbCouch extends QueryBuilderAbstractCouch implements DbInterface, ArrayAccess
{
    public const __version = '0.1.2';
    private const BASE_URL = "http://{host}:{port}";

    public string $baseUrl;
    private string $authHeader;
    private string $database;
    private array $params = [];

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
    return $this;
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
    public function query(string $query = '', array $params = []): DbResultCouch
    {
        if (!$query) {
            $query = JsonEncoder::toJson($this->get());
        }
        Log::d('query',$query);
        $httpRequest = new HttpRequest();
        $url = $this->baseUrl . "/{$this->database}/_find";
        $httpRequest->set($url, $query, [$this->authHeader,'Content-Type: application/json']);

        $result = null;
        $httpRequest->post(function($response) use (&$result) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Query failed: " . ($response[0]['error'] ?? 'Unknown error'));
            }
            $result = new DbResultCouch($response[0]);
        });

        return $result;
    }

    # @ DbSqlInterface
    public function insert(): void
    {
        if (empty($this->params)) {
            throw new Exception("Empty params");
        }

        $httpRequest = new HttpRequest();
        $url = $this->baseUrl . "/$this->database";
        $httpRequest->set($url, JsonEncoder::toJson($this->params), [$this->authHeader, "Content-Type: application/json"]);
        $httpRequest->post(function($response) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Insert failed: " . ($response[0]['error'] ?? 'Unknown error'));
            }
        });
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

        # update
        $httpRequest = new HttpRequest();
        $url = $this->baseUrl . "/{$this->database}". "/{$this->params['_id']}";
        $httpRequest->set($url, JsonEncoder::toJson($this->params), [$this->authHeader, "Content-Type: application/json"]);
        $httpRequest->put(function($response) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Update failed: " . ($response[0]['error'] ?? 'Unknown error'));
            }
        });
    }

    # @ DbSqlInterface
    public function delete(): void
    {
        if (empty($this->params)) {
            throw new Exception("Empty parameters or selector is missing");
        }

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

        parent::init();

        # update
        $httpRequest = new HttpRequest();
        $url = $this->baseUrl . "/{$this->database}". "/{$this->params['_id']}";
        $httpRequest->set($url, JsonEncoder::toJson($this->params), [$this->authHeader, "Content-Type: application/json"]);
        $httpRequest->put(function($response) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Update failed: " . ($response[0]['error'] ?? 'Unknown error'));
            }
        });
    }

    public function createDatabase(string $dbname): void
    {
        $httpRequest = new HttpRequest();
        $dbUrl = $this->baseUrl . "/$dbname";
        $httpRequest->set($dbUrl, "", [$this->authHeader,"Content-Type: application/json"]);
        $httpRequest->put(function($response) use ($dbname) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Failed to create database '$dbname'");
            }
        });
    }

    # @ QueryBuilderAbstractCouch
    public function total(string $column_name = '_id'): int
    {
        $this->set('fields', [$column_name]);
        $this->set('limit', 1);
        $query = $this->get();
        $query['execution_stats'] = true;

        $result = $this->query(JsonEncoder::toJson($query));

        if ($result instanceof DbResultCouch) {
            $executionStats = $result->get_execution_stats();
            if (is_array($executionStats) && isset($executionStats['total_docs'])) {
                return (int)$executionStats['total_docs'];
            }
        }

        // 실행 통계를 얻지 못한 경우, 전체 문서를 가져와서 카운트
        $this->init(); // 쿼리 파라미터 초기화
        $this->set('fields', ['_id']);
        $allDocsResult = $this->query(JsonEncoder::toJson($this->get()));

        return $allDocsResult->num_rows();
    }

    # @ QueryBuilderAbstractCouch
    public function table(...$tables): self
    {
        $this->selectDB($tables[0]);
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

    public function beginTransaction(): bool
    {
        $this->params = [];
        return true;
    }

    public function commit(): bool
    {
        $this->params = [];
        return true;
    }

    public function rollBack(): bool
    {
        $this->params = [];
        return true;
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
        // Implement if needed
    }

    public function __get(string $propertyName)
    {
        if (property_exists(__CLASS__, $propertyName)) {
            if ($propertyName == 'query') {
                return JsonEncoder::toJson($this->get());
            } else {
                return $this->{$propertyName};
            }
        }
    }
}