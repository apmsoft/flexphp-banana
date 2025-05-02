<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\TaskFlow;

/**
$enums = [
    [\\Columns\\IdEnum,[]],
    [\\Columns\\LevelEnum => ["optional"]]
];
*/
class ValidationBasicTask
{
    public function __construct(
        private TaskFlow $task,
        private array $enums
    ){}

    public function execute(array $requested) : void
    {
        try{
            foreach ($this->enums as $enum => $options) {
                $key = $enum->value;
                $enum->validate($requested[$key] ?? '', $options);
            }
        }catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
}