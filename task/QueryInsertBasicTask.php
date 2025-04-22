<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Traits\FidTrait;

/**
$enums = [
    IdEnum::_ID => [],
    CategoryEnum::CATEGORY => [],
    RegiDateEnum::REGI_DATE => [],
    TitleEnum::TITLE => [],
    LevelEnum::LEVEL => [\R::arrays('level')]
];
*/
class QueryInsertBasicTask
{
    use FidTrait;

    string $table = '';
    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private array $enums
    ){}

    #@ Fid
    public function getTable(): string
    {
        return $this->table;
    }

    #@ Fid
    public function getFidColumnName(): string{
        $result = '';
        if (in_array(FidEnum::FID, $this->enums, true)) {
            $result = FidEnum::FID();
        }
        return $result;
    }

    public function execute(string $table, array $requested) : void {
        $this->table = $table;

        $this->db->beginTransaction();
        foreach ($this->enums as $enum => $options) {
            $columnName = $enum->value;
            if($enum == FidEnum::FID()){
                $this->db[$columnName] = $this->createParentFid();
            }else{
                $this->db[$columnName] = $column->filter($requested[$columnName] ?? '', $options);
            }
        }
        $db->table($table)->insert();
        $this->db->commit();
    }
}