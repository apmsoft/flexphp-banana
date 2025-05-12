<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Log;

class QueryDeleteBasicTask
{
    public const __version = '0.1.0';

    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private string $table
    ){}

    private function _where(string | array $where) : void {
        if (!empty($where))
        {
            if(is_string($where)){
                $this->db->where($where);
            }
            else if(is_array($where)){
                $this->db->where(...$where);
            }
        }
    }

    public function execute(string | array $where) : void
    {
        if(!empty($this->_where($where)))
        {
            try{
                $this->db->beginTransaction();
                $this->db->table($this->table)->where()->delete();
                $this->db->commit();
            }catch(\Exception $e){
                Log::e($e->getMessage());
                throw new \Exception($e->getMessage());
            }
        }
    }
}