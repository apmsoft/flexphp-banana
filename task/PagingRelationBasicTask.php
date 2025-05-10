<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Paging\Relation;
use Flex\Banana\Classes\Log;

class PagingRelationBasicTask
{
    public const __version = '0.2.2';

    public function __construct(
        private TaskFlow $task,
        private int $total_record,
        private int $page
    ){}

    public function execute(int $page_count=10, int $block_limit=5) : array
    {
        $paging   = new Relation( $this->total_record ?? 0, $this->page ?? 1 );
        $relation = $paging->query( $page_count, $block_limit )->build()->paging();

        return [
            "page"           => $paging->page,
            "totalPage"      => $paging->totalPage,
            "qLimitStart"    => $paging->qLimitStart,
            "qLimitEnd"      => $paging->qLimitEnd,
            "totalRecord"    => $paging->totalRecord,
            "blockStartPage" => $paging->blockStartPage,
            "blockEndPage"   => $paging->blockEndPage,
            "relation"       => $relation
        ];
    }
}