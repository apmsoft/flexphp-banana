<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;

/**
$enums = [
    [\\Columns\\IdEnum,[]],
    [\\Columns\\CategoryEnum,[]],
    [\\Columns\\LevelEnum, [@arrays.level]]
];
*/
class QueryUpdateBasicTask
{
    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private array $enums
    ){}

    public function execute(string $table, string $where, array $requested) : void 
    {
        $this->db->beginTransaction();
        foreach ($this->enums as [$enum, $options]) {
            $columnName = $enum->value;
            $this->db[$columnName] = $enum->filter($requested[$columnName] ?? '', $options);
        }
        $this->db->table($table)->where($where)->update();
        $this->db->commit();
    }
}