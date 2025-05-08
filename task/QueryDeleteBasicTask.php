<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Utils\Requested;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Log;

/**
$enums = [
    [\Columns\LevelEnum , R::arrays('category'),"optional"]
];
$chks = []
*/
class QueryDeleteBasicTask
{
    public const __version = '0.2.0';
    private $enum;

    public function __construct(
        private TaskFlow $task,
        private DbManager $db,
        private array $enums
    ){}

    public function execute(string $table, array $chks) : void
    {
        foreach ($this->enums as $item) {
            if (!is_array($item) || count($item) === 0) {
                continue;
            }

            $enum = $item[0];
            $options = array_slice($item, 1);

            // 필요한 경우 클래스 문자열을 ENUM 인스턴스로 변환
            if (is_string($enum) && enum_exists($enum)) {
                $this->enum = $enum::cases()[0];
            }
        }

        foreach($chks as $_id)
        {
            # 데이터 체크
            if(trim($_id))
            {
                $data = $this->db->table($table)
                    ->select($this->enum->value)
                        ->where($this->enum->value,$_id)
                            ->query()->fetch_assoc();
                if(isset( $data[$this->enum->value] ))
                {
                    try{
                        $this->db->beginTransaction();
                        $this->db->table(table)->where($this->enum->value,$_id)->delete();
                        $this->db->commit();
                    }catch(\Exception $e){
                        Log::e($e->getMessage());
                        throw new \Exception($e->getMessage());
                    }
                }
            }
        }
    }
}