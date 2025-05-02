<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Db\DbResultSql;
use Flex\Banana\Classes\Db\DbResultCouch;
/**
$enums = [
    [\\Columns\\IdEnum,[]],
    [\\Columns\\CategoryEnum,[]],
    [\\Columns\\LevelEnum, [@arrays.level]]
];
*/
class QueryBasicTask
{
    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private array $enums
    ){}

    public function execute(DbResultCouch | DbResultSql $result) : array
    {
        $data = [];
        while ($row = $result->fetch_assoc())
        {
            $formattedRow = [];
            foreach ($this->enums as [$enum, $options]) {
                $columnName = $enum->value;
                $formattedRow[$columnName] = $enum->format($row[$columnName], $options);
            }
            $data[] = $formattedRow;
        }

        return $data;
    }
}