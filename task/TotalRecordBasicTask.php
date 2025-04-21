<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;

class TotalRecordBasicTask
{
    public function __construct(
        private TaskFlow $task,
        private DbManager $db
    ){}

    public function execute() : void
    {
        // 데이터베이스에서 총 레코드 수 조회
        $this->task->total_record = $this->db->table( $this->task->table ?? '')
            ->where( $this->task->where ?? '' )
                ->total();
    }
}