<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\WhereCouch;
use Flex\Banana\Classes\Log;
abstract class QueryBuilderAbstractCouch
{
    public const __version = '0.0.2';
    protected array $query_params;

    protected const _QUERY_INIT_PARAMS_ = [
        'selector'  => ["_id" => ['$gt' => null]],
        'fields'    => [],
        'sort'      => [],
        'limit'     => null,
        'skip'      => null,
        'use_index' => null
    ];

    public function __construct(
        protected WhereCouch $whereCouch
    )
    {
        $this->init();
    }

    abstract public function table(...$tables) : mixed;
    abstract public function select(...$columns) : mixed;
    abstract public function where(...$where) : mixed;
    abstract public function orderBy(...$orderby) : mixed;
    abstract public function limit(...$limit) : mixed;
    abstract public function total(string $column_name) : int;
    abstract public function useIndex(...$index): self;

    public function init(): void
    {
        $this->query_params = self::_QUERY_INIT_PARAMS_;
    }

    public function set(string $key, $value): void
    {
        if($key == 'selector'){
            $this->query_params[$key] = new \stdClass();
        }
        $this->query_params[$key] = $value;
    }

    public function get(): array
    {
        $query = [];
        foreach ($this->query_params as $key => $value) {
            if ($value !== null) {
                if($key == 'sort'){
                    if(!empty($this->query_params[$key])){
                        $query[$key] = $value;
                    }
                }else $query[$key] = $value;
            }
        }

        return $query;
    }

    public function bindingDNS (string $tpl, array $dsn_options) : string 
    {
        preg_match_all("/({+)(.*?)(})/", $tpl, $matches);
        $patterns = $matches[0];
        $columns  = $matches[2];

        # binding
        foreach($patterns as $idx => $text){
            $column_name = $columns[$idx];
            $render_args[$text] = (trim($dsn_options[$column_name])) ? $dsn_options[$column_name] :'';
        }
        return trim(strtr($tpl, $render_args));
    }

    protected function buildWhere(array $conditions): array
    {
        $this->whereCouch->__construct();
        $this->whereCouch->begin('and');
        if (is_array($conditions) && count($conditions) >= 2) {
            if (count($conditions) == 2) {
                $this->whereCouch->case($conditions[0], '=', $conditions[1]);
            } elseif (count($conditions) == 3) {
                $this->whereCouch->case($conditions[0], $conditions[1], $conditions[2]);
            }
        }
        $this->whereCouch->end();
        return $this->whereCouch->__get('where');
    }
}