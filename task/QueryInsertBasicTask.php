<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Traits\FidTrait;
use Flex\Banana\Classes\Log;

class QueryInsertBasicTask
{
    public const __version = '0.2.1';
    use FidTrait;

    private $table = '';

    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
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

    public function execute(string $table, array $requested): void
    {
        $this->table = $table;

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

                if ($columnName == "fid") {
                    $this->db[$columnName] = $this->createParentFid();
                } else {
                    $this->db[$columnName] = $enum->filter($requested[$columnName] ?? '', ...$options);
                }
            }

            $this->db->table($table)->insert();
            $this->db->commit();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}