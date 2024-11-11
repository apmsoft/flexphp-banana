<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\WhereInterface;
use Flex\Banana\Classes\Log;

# 데이터베이스 QUERY구문에 사용되는 WHERE문 만드는데 도움을 주는 클래스
class WhereCouch implements WhereInterface
{
	public const __version = '0.1.0';
	private array $where_group = [];
	private string $current_group = '';
	private string $current_coord = '';
	private array $where_groups_data = [];
	private string $coord = 'AND'; # 전체 그룹을 마지막으로 묶을 coord

	# void
	# @fields : name+category+area 복수필드
	# @coord : [AND | OR]
	public function __construct(string $coord = 'AND')
	{
		$this->coord = $coord;
		$this->init();
	}

	# where 그룹묶기 시작
	public function begin(string $coord) : WhereCouch
	{
		// Log::d(__CLASS__,__METHOD__, $coord);
		$groupname = strtr(microtime(),[' '=>'','0.'=>'w']);
		if(!isset($this->where_group[$groupname])){
			$this->where_group[$groupname] = [];
		}

		# end 자동닫기
		if($this->current_group){
			$this->end();
		}

		# 현재그룹 시작
		$this->current_group = $groupname;
		$this->current_coord = $coord;
	return $this;
	}

	# where 그룹묶기 종료
	public function end() : WhereCouch
    {
		// Log::d(__METHOD__,$this->where_group);
        // 현재 그룹에 조건이 있으면
        if (count($this->where_group[$this->current_group])) {
            // 조건을 배열로 저장
            $group_conditions = ['$' . strtolower($this->current_coord) => []];
            foreach ($this->where_group[$this->current_group] as $condition) {
                $group_conditions['$' . strtolower($this->current_coord)][] = $condition;
            }
            // 조건을 and, or로 구분하여 추가
            $this->where_groups_data[]=$group_conditions;
        }

        // 현재 그룹과 coord 초기화
        $this->current_group = '';
        $this->current_coord = '';

        return $this;
    }

	# void
	# 구문어를 만든다.
	# @where_str : name='홍길동'
	# @condition : [=,!=,<,>,<=,>=,IN,LIKE-R=dd%,LIKE-L=%dd,LIKE=%dd%]
	# @value : NULL | VALUE | % | Array
	public function case(string $field_name, string $condition ,mixed $value, bool $is_qutawrap=true, bool $join_detection=true) : WhereCouch
	{
		// Log::d(__METHOD__);
		$is_append = false;
		if($value == "0") $is_append = true;
		else if($value && $value !=''){
			$is_append = true;
		}

		# where 문을 그룹별로 묶기
		if($is_append)
		{
			$in_value = [];
			if (is_array($value)){ // array
				$in_value = $value;
			} else if (strpos($value, ",") !==false){
				$in_value = explode(',', $value);
			} else{
				$in_value[] = $value;
			}

			$condition = strtolower($condition);
			if($condition == 'like' || $condition == 'like-r' || $condition == 'like-l'){
				foreach($in_value as $word)
				{
					// $_word = preg_replace("/[#\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i",' ',$word);
					$_word = preg_replace("/[#\&\+%@=\/\\;,'\"\^`~|\!\?\*$#<>()\[\]\{\}]/i",' ',$word);

					$this->where_group[$this->current_group][][$field_name] = [
                        '$regex' => $this->buildRegexForLike($condition, $_word)
                    ];
				}
			}
			else if($value == 'null'){
				$this->where_group[$this->current_group][][$field_name] = null;
			}
			else{
				// 다른 조건 처리
                if (count($in_value) === 1) {
                    // 단일 값일 경우
                    $this->where_group[$this->current_group][][$field_name] = [
                        $this->mapConditionToOperator($condition) => $in_value[0] // 단일 값 사용
                    ];
                } else {
                    // 다수의 값일 경우 (IN 조건)
                    $this->where_group[$this->current_group][][$field_name] = [
                        $this->mapConditionToOperator($condition) => $in_value
                    ];
                }
			}
		}
	return $this;
	}

	# 상속한 부모 프라퍼티 값 포함한 가져오기
	public function __get($propertyName)
    {
		// Log::d(__METHOD__,$propertyName);
        if ($propertyName == 'where') 
		{
			// 아직 종료되지 않은 그룹이 있으면 종료
			if ($this->current_group) {
				$this->end();
			}

			// Log::d('where_groups_data',$this->where_groups_data);

			// 그룹 데이터를 배열로 리턴
			$result = [];
			if(count($this->where_groups_data) > 1){
				$_coord = '$' . strtolower($this->coord);
				$result = [$_coord => $this->where_groups_data];
			}else{
				$result = (isset($this->where_groups_data[0])) ? $this->where_groups_data[0] : $this->where_groups_data;
			}
			$this->init();
			return $result;
		}else{
			return $this->{$propertyName};
		}
    }

    private function mapConditionToOperator(string $condition): string
    {
        return match ($condition) {
            '=' => '$eq',
            '!=' => '$ne',
            '<' => '$lt',
            '<=' => '$lte',
            '>' => '$gt',
            '>=' => '$gte',
            'in' => '$in',
            'not in' => '$nin',
            default => '$eq'
        };
    }

    private function buildRegexForLike(string $condition, string $value): string
    {
        $value = preg_quote($value, '/');
        return match ($condition) {
            'like'   => ".*$value.*",
            'like-r' => "$value.*",
            'like-l' => ".*$value",
            default  => $value,
        };
    }

	# 초기화
	private function init() : void {
		$this->current_group = '';
		$this->current_coord = '';
		$this->where_group   = [];
		$this->where_groups_data = [];
	}

	public function fetch() : array
	{
        if ($this->current_group) {
            $this->end();
        }
		$result = $this->where_group;
		$this->init();
	return $result;
	}


	public function __destruct(){
		$this->current_group     = '';
		$this->current_coord     = '';
		$this->where_group       = [];
		$this->where_groups_data =  [];
	}
}