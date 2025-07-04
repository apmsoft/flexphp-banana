<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Log;

class QueryDeleteBasicTask
{
    public const __version = '0.2.0';

    public function __construct(
        private DbManager $db,
        private string $table
    ){}

    private function _where(string|array $where): string|null {
        if (empty($where)) return null;

        if (is_string($where)) {
            return $where;
        }

        if (is_array($where)) {
            return $this->db->buildWhere($where);
        }

        return null;
    }

    public function execute(string | array $where) : void
    {
        $_where = $this->_where($where['where'] ?? '');
        if($_where)
        {
            try{
						if ($this->db->inTransaction()) {
							$this->db->rollBack();
						}
						$data = $this->db->table($this->table)->where($_where)->query()->fetch_assoc();
						if($data){
								if ($this->db->inTransaction()) {
                                    $this->db->rollBack();
                                }
								$this->db->beginTransaction();
								$this->db->table($this->table)->where($_where)->delete();
								$this->db->commit();
						}
            }catch(\Exception $e){
                Log::e($e->getMessage());
                // throw new \Exception($e->getMessage());
            }
        }
    }
}