<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;

/**
$enums = [
    [\\Columns\\IdEnum,[]]
];
$chks = []
*/
class QueryDeleteBasicTask
{
    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private \BackedEnum $enum
    ){}

    public function execute(string $table, array $chks) : void
    {
        foreach($chks as $_id)
        {
            # 데이터 체크
            if(trim($_id))
            {
                $data = $this->db->table($table)
                    ->select($this->enum->value)
                        ->where($this->enum->value,$_id)
                            ->query()->fetch_assoc();
                if(isset( $data[$this->enum->value] ))
                {
                    try{
                        $this->db->beginTransaction();
                        $this->db->table(table)->where($this->enum->value,$_id)->delete();
                        $this->db->commit();
                    }catch(\Exception $e){
                        Log::e($e->getMessage());
                        throw new \Exception($e->getMessage());
                    }
                }
            }
        }
    }
}