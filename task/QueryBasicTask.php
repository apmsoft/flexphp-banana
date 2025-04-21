<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;

/**
$enums = [
    IdEnum::_ID => [],
    CategoryEnum::CATEGORY => [],
    RegiDateEnum::REGI_DATE => [],
    TitleEnum::TITLE => [],
    LevelEnum::LEVEL => [\R::arrays('level')]
];
*/
class QueryBasicTask
{
    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private array $enums
    ){}

    private function query() : DbManager
    {
        // 데이터를 쿼리하여 가져옵니다
        return $this->db->table( $this->task->table )
            ->select(...array_map(fn($column) => $column->value, $this->task->enums))
            ->where($this->task->where) // where 조건을 TaskFlow에서 설정된 값으로 사용
            ->limit($this->task->limit)
            ->query();
    }

    private function singleQuery() : void
    {
        $result = $this->query();
        $this->task->data = $result->fetch_assoc();
    }

    private function multiQuery () : void
    {
        $result = $this->query();
        $data = [];
        while ($row = $result->fetch_assoc())
        {
            $formattedRow = [];
            foreach ($this->task->enums as $enum => $options) {
                $columnName = $enum->value;
                $formattedRow[$columnName] = $enum->format($row[$columnName], $options);
            }
            $data[] = $formattedRow;
        }

        $this->task->data = $data;
    }

    public function execute( string $queryType) : void {
        if($queryType == 'multi' || $queryType == 'm') {
            $this->multiQuery();
        }else{
            $this->singleQuery();
        }
    }
}