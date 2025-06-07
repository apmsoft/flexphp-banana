<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\Db\WhereHelper;
use Flex\Banana\Classes\Log;

class QueryWhereCaseBasicTask
{
    public const __version = '0.2.0';

    private mixed $where = '';

    public function __construct(
        private WhereHelper $dbWhere
    ){}

    # execute('and', "name","=","나당");
    # execute('and', ["name","=","나다"]);
    # execute('and', ["name","=","나다"], ["age",">=",19]);
    public function execute(string $coord, ...$params) : void
    {
        $conditions = [];

        # 단일 조건: "name", "=", "나당"
        if (count($params) === 3 && !is_array($params[0])) {
            $conditions[] = [$params[0], $params[1], $params[2]];
        } else {
            foreach ($params as $index => $param) {
                if (!is_array($param)) {
                    throw new \InvalidArgumentException(
                        "Parameter at index {$index} must be an array of 3 elements."
                    );
                }
                if (count($param) !== 3) {
                    throw new \InvalidArgumentException(
                        "Each condition array must contain exactly 3 elements: field, operator, value. Problem at index {$index}."
                    );
                }
                $conditions[] = $param;
            }
        }

        # case
        $coordCase = strtoupper($coord);
        $this->dbWhere->begin($coordCase);

        foreach($conditions as $casewh){
            list($fieldname, $condition, $value) = $casewh;
            Log::d($fieldname,$condition,$value);
            $this->dbWhere->case($fieldname, $condition, $value);
        }

        $this->dbWhere->end();
    }

    public function __get($propertyName) : mixed
    {
        Log::d(__CLASS__, 'propertyName', $propertyName);
        if ($propertyName === 'where') {
            $this->where = $this->dbWhere->__get('where');
            return $this->where;
        }
        return null;
    }
}