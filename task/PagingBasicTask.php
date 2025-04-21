<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;

class PagingBasicTask
{
    public function __construct(
        private TaskFlow $task
    ){}

    public function execute() : void
    {
        $this->task->paging = new Relation( totalRecord: $this->task->total_record ?? 0, page: $this->task->page ?? 1);
        $this->task->relation = $this->task->paging->query( pagecount: $this->task->page_count ?? 10, blockLimit: $this->task->block_limit ?? 10)->build()->paging();
    }
}