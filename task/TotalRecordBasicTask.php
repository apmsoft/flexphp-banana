<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Log;

class TotalRecordBasicTask
{
    public const __version = '0.1.0';

    public function __construct(
        private DbManager $db
    ){}

    public function execute(string $table, string $where) : int
    {
        return $this->db->table( $table )->where($where)->total();
    }
}