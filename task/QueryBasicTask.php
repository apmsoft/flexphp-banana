<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Db\DbResultSql;
use Flex\Banana\Classes\Db\DbResultCouch;
use Flex\Banana\Classes\Log;

class QueryBasicTask
{
    public const __version = '0.2.0';

    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private array $enums
    ){}

    public function execute(DbResultCouch | DbResultSql $result) : array
    {
        $data = [];
        try {
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