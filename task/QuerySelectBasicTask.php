<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Traits\FidTrait;
use Flex\Banana\Classes\Log;

class QuerySelectBasicTask
{
    public const __version = '0.2.1';

    use FidTrait;

    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private string $table,
        private array $enums
    ){
        # query start
        $this->db->table($this->table)->select(
            $this->findSelectColumns()
        );
    }

    #@ Fid
    public function getTable(): string {
        return $this->table;
    }

    #@ Fid
    public function getFidColumnName(): string {
        return "fid";
    }

    #@ Select Columns String 목록 만들기
    private function findSelectColumns(): string
    {
        $columns = [];
        foreach ($this->enums as $item)
        {
            if (!is_array($item) || count($item) === 0) {
                continue;
            }

            $enum = $item[0];

            // 필요한 경우 클래스 문자열을 ENUM 인스턴스로 변환
            if (is_string($enum) && enum_exists($enum)) {
                $enum = $enum::cases()[0];
            }

            if (!($enum instanceof \BackedEnum)) {
                continue;
            }

            $columns[] = $enum->value;
        }

        return implode(",", $columns);
    }

    private function matchs(string $query, mixed $qitem) : void
    {
        match($query) {
            "where"   => $this->_where($qitem ?? ''),
            "orderBy" => $this->_orderBy($qitem ?? ''),
            "limit"   => $this->_limit($qitem ?? ''),
            default   => throw new \Exception("Not Found {$query}")
        };
    }

    private function _where(string | array $where) : void {
        if (!empty($where))
        {
            if(is_string($where)){
                $this->db->where($where);
            }
            else if(is_array($where)){
                $this->db->where(...$where);
            }
        }
    }

    private function _orderBy(string | array $orderby) : void {
        if (!empty($orderby))
        {
            if(is_string($orderby)){
                $this->db->orderBy($orderby);
            }
            else if(is_array($orderby)){
                $this->db->orderBy(...$orderby);
            }
        }
    }

    private function _limit(int|array $limit): void {
        if (is_int($limit)) {
            $this->db->limit($limit);
        } elseif (is_array($limit)) {
            $this->db->limit(...$limit);
        }
    }

    /**
    "params": [{
        "where" : "_id="1" | ["_id","asfdsd"] | ["age",">=","100"] | [["_id","1"],["age",">=",100]],
        "orderBy" : "regidate DESC" | ["regidate DESC"] | ["regi_date DESC", "fid ASC"],
        "limit": 10 | [0,10]
    }]
    */
    public function execute(array $queries) : array
    {
        try {
            $queryString = '';
            foreach($queries as $query => $qitem){
                $this->matchs($query, $qitem);
            }

            $queryString = $this->db->query;
            Log::d('queryString',$queryString);
            $result = $this->db->query( $queryString );

            $data = [];
            while ($row = $result->fetch_assoc())
            {
                $formattedRow = [];
                foreach ($this->enums as $item)
                {
                    if (!is_array($item) || count($item) === 0) {
                        continue;
                    }

                    $enum = $item[0];
                    $options = array_slice($item, 1);

                    // 필요한 경우 클래스 문자열을 ENUM 인스턴스로 변환
                    if (is_string($enum) && enum_exists($enum)) {
                        $enum = $enum::cases()[0];
                    }

                    if (!($enum instanceof \BackedEnum)) {
                        continue;
                    }

                    $columnName = $enum->value;
                    $formattedRow[$columnName] = $enum->format($row[$columnName] ?? '', ...$options);
                }
                $data[] = $formattedRow;
            }

            return $data;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}