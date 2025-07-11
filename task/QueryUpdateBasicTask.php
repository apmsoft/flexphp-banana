<?php
namespace Flex\Banana\Task;

use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;

class QueryUpdateBasicTask
{
    public const __version = '0.3.0';

    public function __construct(
        private DbManager $db,
        private string $table,
        private array $preset
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

    public function execute(string | array $where, array $requested) : void
    {
        try {
					if ($this->db->inTransaction()) {
						$this->db->rollBack();
					}
					$this->db->beginTransaction();
					foreach ($this->preset as $item)
					{
							if (!is_array($item) || count($item) === 0) {
									continue;
							}

							$enum = $item[0];
							$options = array_slice($item, 1);

							// 필요한 경우 클래스 문자열을 ENUM 인스턴스로 변환
							if (is_string($enum) && enum_exists($enum)) {
									$enum = $enum::cases()[0];
							}

							if (!($enum instanceof \BackedEnum)) {
									continue;
							}

							$columnName = $enum->value;
							$this->db[$columnName] = $enum->filter($requested[$columnName] ?? '', ...$options);
					}
					$this->db->table($this->table)->where($this->_where($where))->update();
					$this->db->commit();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}