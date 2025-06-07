<?php
namespace Flex\Banana\Task;

use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Traits\FidTrait;
use Flex\Banana\Classes\Log;

class SortByFidBasicTask
{
    public const __version = '0.1.0';
    use FidTrait;

    public function __construct(
        private DbManager $db,
        private string $table
    ) {}

    #@ Fid
    public function getTable(): string
    {
        return $this->table;
    }

    #@ Fid
    public function getFidColumnName(): string
    {
        return "fid";
    }

    public function execute(string $mode, string $fid, string $columnName = '_id'): void
    {
        Log::d($mode, $fid, $columnName);
        if($mode == 'down'){
            # 화살표 위
            $data = $this->getSortDown($fid);
            $this->changeSortFid ($data['cur_fids'], $data['ano_fids'], $columnName);
        }else if($mode == 'up'){
            # 화살표 아래
            $data = $this->getSortUp($fid);
            $this->changeSortFid ($data['cur_fids'], $data['ano_fids'], $columnName);
        }
    }
}