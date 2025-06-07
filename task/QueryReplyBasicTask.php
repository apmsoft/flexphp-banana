<?php
namespace Flex\Banana\Task;

use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Traits\FidTrait;
use Flex\Banana\Classes\Log;

class QueryReplyBasicTask
{
    public const __version = '0.1.0';
    use FidTrait;

    public function __construct(
        private DbManager $db,
        private string $table,
        private array $enums
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

    public function execute(string|array $where, array $requested): void
    {
        # 부모글이 있는지 체크하기
        $_where = $this->_where($where['where'] ?? '');
        if($_where)
        {
            $data = $this->db->table($this->table)->where($_where)->query()->fetch_assoc();
            if($data){
                try {
                    $this->db->beginTransaction();

                    foreach ($this->enums as $item) {
                        if (!is_array($item) || count($item) === 0) {
                            continue;
                        }

                        $enum = $item[0];
                        $options = array_slice($item, 1);

                        // 필요한 경우 클래스 문자열을 ENUM 인스턴스로 변환
                        if (is_string($enum) && enum_exists($enum)) {
                            $cases = $enum::cases();
                            $enum = $cases[0] ?? null;
                        }

                        if (!($enum instanceof \BackedEnum)) {
                            continue;
                        }

                        $columnName = $enum->value;
                        if ($columnName == $this->getFidColumnName()) {
                            $this->db[$columnName] = $this->createChildFid($data[$columnName]);
                        } else {
                            $this->db[$columnName] = $enum->filter($requested[$columnName] ?? '', ...$options);
                        }
                    }

                    $this->db->table($this->table)->insert();
                    $this->db->commit();
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            }
        }
    }
}