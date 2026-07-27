<?php

declare(strict_types=1);

namespace Flex\Banana\Classes\Array;

#
# 1차원 배열 지원 가능:
# sum, min, max, avg, map, reduce, stream, slice, split, fill, count

# 2차원 배열 전용:
# find, findAll, findWhere, queryStream, select, sorting, unique, isnull, dropnull, fillnull, pluck, changeKeys
# 배열 처리용 체이닝 헬퍼.
# 요구 PHP 버전: PHP 8.0 이상
class ArrayHelper implements \Countable, \IteratorAggregate, \JsonSerializable
{
    public const __version = '2.0.0';

    # @var array<int, string>
    private const COMPARISON_OPERATORS = [
        '>', '>=', '<', '<=', '=', '==', '===', '!=', '<>', '!==',
        'LIKE', 'CONTAINS', 'NOT LIKE', 'NOT CONTAINS',
        'LIKE-R', 'STARTS WITH', 'LIKE-L', 'ENDS WITH',
        'IN', 'NOT IN', 'IN STRICT', 'NOT IN STRICT',
        'BETWEEN', 'NOT BETWEEN', 'BETWEEN EXCLUSIVE', 'NOT BETWEEN EXCLUSIVE',
        'IS NULL', 'IS NOT NULL',
    ];

    # @var array<mixed>
    private array $origin;

    # @var array<mixed>
    private array $value;

    #
    # @param array<mixed> $value
    public function __construct(array $value = [])
    {
        $this->value = $value;
        $this->origin = $value;
    }

    #
    # @param array<mixed> $value
    public static function make(array $value = []): static
    {
        return new static($value);
    }

    # 원본 상태로 복원합니다.
    public function reset(): self
    {
        $this->value = $this->origin;
        return $this;
    }

    #
    # 현재 데이터와 원본 데이터를 모두 교체합니다.
    #
    # @param array<mixed> $newValue
    public function init(array $newValue): self
    {
        $this->value = $newValue;
        $this->origin = $newValue;
        return $this;
    }

    # @return array<mixed>
    public function all(): array
    {
        return $this->value;
    }

    # @return array<mixed>
    public function original(): array
    {
        return $this->origin;
    }

    public function isEmpty(): bool
    {
        return $this->value === [];
    }

    public function count(): int
    {
        return count($this->value);
    }

    public function getIterator(): \Traversable
    {
        return $this->stream();
    }

    # @return array<mixed>
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    #
    # 다차원 배열을 특정 열로 정렬합니다.
    # 숫자 키는 0부터 다시 정렬됩니다(usort 동작).
    #
    # @param string|int $key
    public function sorting(string|int $key, string $sorting = 'ASC'): self
    {
        $direction = strtoupper(trim($sorting));
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new \InvalidArgumentException(
                "지원하지 않는 정렬 방향입니다: {$sorting}. ASC 또는 DESC를 사용하세요."
            );
        }

        $this->assertRows($this->value, __METHOD__);
        foreach ($this->value as $index => $row) {
            if (!array_key_exists($key, $row)) {
                throw new \OutOfBoundsException(
                    sprintf('%s: %s번째 행에 정렬 키 [%s]가 없습니다.', __METHOD__, (string) $index, (string) $key)
                );
            }
        }

        usort(
            $this->value,
            static function (array $left, array $right) use ($key, $direction): int {
                $comparison = $left[$key] <=> $right[$key];
                return $direction === 'ASC' ? $comparison : -$comparison;
            }
        );

        return $this;
    }

    #
    # 지정 열에서 첫 번째 일치 행을 찾습니다.
    # 찾지 못하면 value는 빈 배열이 됩니다.
    public function find(string|int $key, mixed $val, bool $strict = false): self
    {
        $this->assertRows($this->value, __METHOD__);

        foreach ($this->value as $row) {
            if (
                array_key_exists($key, $row)
                && $this->valuesEqual($row[$key], $val, $strict)
            ) {
                $this->value = $row;
                return $this;
            }
        }

        $this->value = [];
        return $this;
    }

    #
    # 지정 열에서 여러 값 중 하나와 일치하는 행을 찾습니다.
    #
    # 사용 예:
    # - findAll('status', 'ready', 'done')
    # - findAll('status', ['ready', 'done'])
    public function findAll(string|int $key, mixed ...$params): self
    {
        $this->assertRows($this->value, __METHOD__);

        if ($params === []) {
            $this->value = [];
            return $this;
        }

        $values = count($params) === 1 && is_array($params[0])
            ? array_values($params[0])
            : $params;

        $result = [];
        foreach ($this->value as $row) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            foreach ($values as $expected) {
                if ($this->valuesEqual($row[$key], $expected, false)) {
                    $result[] = $row;
                    break;
                }
            }
        }

        $this->value = $result;
        return $this;
    }

    #
    # 각 행에서 지정한 키만 남깁니다.
    #
    # 사용 예:
    # - select('id', 'name')
    # - select(['id', 'name'])
    # - select('id', 'name', false)
    #
    # 마지막 bool 값은 기존 API의 inplace 옵션입니다. 어느 경우든 체이닝 객체의
    # value는 결과로 교체되며, false일 때만 별도 결과 배열을 만들어 교체합니다.
    public function select(mixed ...$args): self
    {
        $inplace = true;
        if ($args !== [] && is_bool($args[array_key_last($args)])) {
            $inplace = (bool) array_pop($args);
        }

        $keys = count($args) === 1 && is_array($args[0])
            ? array_values($args[0])
            : $args;
        $keys = $this->validateKeys($keys, __METHOD__);

        $this->assertRows($this->value, __METHOD__);
        $keyMap = array_fill_keys($keys, true);

        if ($inplace) {
            foreach ($this->value as $index => $row) {
                $this->value[$index] = array_intersect_key($row, $keyMap);
            }
            return $this;
        }

        $result = [];
        foreach ($this->value as $index => $row) {
            $result[$index] = array_intersect_key($row, $keyMap);
        }
        $this->value = $result;

        return $this;
    }

    #
    # 호출 시점의 value를 Generator로 반환합니다.
    # PHP 배열의 copy-on-write 특성상, 실제 수정 전까지 불필요한 전체 복사는 발생하지 않습니다.
    public function stream(): \Generator
    {
        $source = $this->value;

        return (static function (array $rows): \Generator {
            foreach ($rows as $key => $item) {
                yield $key => $item;
            }
        })($source);
    }

    #
    # 다중 조건 검색 후 내부 value를 결과로 교체합니다.
    #
    # 조건 예:
    # - ['status' => 'active']
    # - ['age' => ['>=', 20]]
    # - ['name' => ['LIKE', 'kim']]
    # - ['id' => ['IN', [1, 2, 3]]]
    # - ['score' => ['BETWEEN', [80, 100]]]
    # - ['deleted_at' => ['IS NULL']]
    #
    # @param array<string|int, mixed> $params
    public function findWhere(
        array $params,
        string $operator = 'AND',
        bool $preserveKeys = false
    ): self {
        $logicalOperator = $this->normalizeLogicalOperator($operator);
        $this->validateWhereParams($params);
        $this->assertRows($this->value, __METHOD__);

        $result = [];
        foreach ($this->value as $key => $row) {
            if (!$this->matchesWhere($row, $params, $logicalOperator)) {
                continue;
            }

            if ($preserveKeys) {
                $result[$key] = $row;
            } else {
                $result[] = $row;
            }
        }

        $this->value = $result;
        return $this;
    }

    #
    # Generator 기반 조건 검색입니다.
    # - 중간 결과 배열을 만들지 않습니다.
    # - 객체의 value/origin을 변경하지 않습니다.
    # - 호출 시점의 value를 사용합니다.
    # - 원본 키를 보존합니다.
    #
    # @param array<string|int, mixed> $params
    public function queryStream(array $params, string $operator = 'AND'): \Generator
    {
        $logicalOperator = $this->normalizeLogicalOperator($operator);
        $this->validateWhereParams($params);

        $source = $this->value;
        $this->assertRows($source, __METHOD__);
        $matcher = function (array $row) use ($params, $logicalOperator): bool {
            return $this->matchesWhere($row, $params, $logicalOperator);
        };

        return (static function (array $rows, callable $matches): \Generator {
            foreach ($rows as $key => $row) {
                if ($matches($row)) {
                    yield $key => $row;
                }
            }
        })($source, $matcher);
    }

    #
    # 조건에 맞는 첫 번째 행의 0 기반 위치를 반환합니다.
    # 찾지 못하면 -1을 반환합니다.
    #
    # @param array<string|int, mixed> $params
    public function findWhereIndex(array $params, string $operator = 'AND'): int
    {
        $logicalOperator = $this->normalizeLogicalOperator($operator);
        $this->validateWhereParams($params);
        $this->assertRows($this->value, __METHOD__);

        $position = 0;
        foreach ($this->value as $row) {
            if ($this->matchesWhere($row, $params, $logicalOperator)) {
                return $position;
            }
            $position++;
        }

        return -1;
    }

    #
    # 지정 열을 기준으로 첫 번째 행만 남기고 중복을 제거합니다.
    # 열이 없는 행들은 동일한 '누락 값'으로 취급합니다.
    public function unique(string|int $columnName, bool $strict = true): self
    {
        $this->assertRows($this->value, __METHOD__);

        $result = [];
        $missing = new \stdClass();

        if ($strict) {
            $seen = [];
            foreach ($this->value as $row) {
                $candidate = array_key_exists($columnName, $row)
                    ? $row[$columnName]
                    : $missing;
                $fingerprint = $this->fingerprint($candidate);

                if (isset($seen[$fingerprint])) {
                    continue;
                }

                $seen[$fingerprint] = true;
                $result[] = $row;
            }
        } else {
            # @var array<int, array{missing: bool, value: mixed}> $seen
            $seen = [];
            foreach ($this->value as $row) {
                $isMissing = !array_key_exists($columnName, $row);
                $candidate = $isMissing ? null : $row[$columnName];
                $duplicated = false;

                foreach ($seen as $seenValue) {
                    if ($isMissing || $seenValue['missing']) {
                        if ($isMissing && $seenValue['missing']) {
                            $duplicated = true;
                            break;
                        }
                        continue;
                    }

                    if ($this->valuesEqual($candidate, $seenValue['value'], false)) {
                        $duplicated = true;
                        break;
                    }
                }

                if ($duplicated) {
                    continue;
                }

                $seen[] = ['missing' => $isMissing, 'value' => $candidate];
                $result[] = $row;
            }
        }

        $this->value = $result;
        return $this;
    }

    #
    # null, 빈 문자열 또는 지정 키 누락이 있는 행만 남깁니다.
    # 키를 생략하면 행 전체 열을 검사합니다.
    #
    # 사용 예: isnull('name', 'email'), isnull(['name', 'email'])
    public function isnull(mixed ...$params): self
    {
        $keys = $this->normalizeKeyArguments($params, __METHOD__);
        $this->assertRows($this->value, __METHOD__);

        $result = [];
        foreach ($this->value as $index => $row) {
            if ($this->rowHasNullLikeValue($row, $keys)) {
                $result[$index] = $row;
            }
        }

        $this->value = $result;
        return $this;
    }

    #
    # null, 빈 문자열 또는 지정 키 누락이 있는 행을 제거합니다.
    # 키를 생략하면 행의 모든 열을 검사합니다.
    # 결과는 0부터 다시 정렬됩니다.
    #
    # 사용 예: dropnull('name', 'email'), dropnull(['name', 'email'])
    public function dropnull(mixed ...$params): self
    {
        $keys = $this->normalizeKeyArguments($params, __METHOD__);
        $this->assertRows($this->value, __METHOD__);

        $result = [];
        foreach ($this->value as $row) {
            if (!$this->rowHasNullLikeValue($row, $keys)) {
                $result[] = $row;
            }
        }

        $this->value = $result;
        return $this;
    }

    #
    # null 값을 채웁니다. includeEmptyString=true이면 빈 문자열도 채웁니다.
    # fillData가 배열이면 열별 대체값 맵으로 처리합니다.
    public function fillnull(mixed $fillData, bool $includeEmptyString = false): self
    {
        $this->assertRows($this->value, __METHOD__);
        $useColumnMap = is_array($fillData);

        foreach ($this->value as $rowIndex => $row) {
            foreach ($row as $column => $currentValue) {
                $shouldFill = $currentValue === null
                    || ($includeEmptyString && $currentValue === '');

                if (!$shouldFill) {
                    continue;
                }

                if ($useColumnMap) {
                    if (array_key_exists($column, $fillData)) {
                        $this->value[$rowIndex][$column] = $fillData[$column];
                    }
                    continue;
                }

                $this->value[$rowIndex][$column] = $fillData;
            }
        }

        return $this;
    }

    #
    # 숫자 인덱스 배열에서 start부터 length개 값을 덮어씁니다.
    # length가 null이면 start부터 현재 배열 끝까지 덮어씁니다.
    public function fill(int $start = 0, ?int $length = null, mixed $value = null): self
    {
        if ($start < 0) {
            throw new \InvalidArgumentException('fill의 start는 0 이상이어야 합니다.');
        }
        if ($length !== null && $length < 0) {
            throw new \InvalidArgumentException('fill의 length는 0 이상이거나 null이어야 합니다.');
        }
        if (!$this->isList($this->value)) {
            throw new \LogicException('fill은 0부터 이어지는 숫자 인덱스 배열에만 사용할 수 있습니다.');
        }
        if ($start > count($this->value)) {
            throw new \OutOfBoundsException('fill의 start는 현재 배열 길이보다 클 수 없습니다.');
        }

        $actualLength = $length ?? max(0, count($this->value) - $start);
        if ($actualLength === 0) {
            return $this;
        }

        $replacement = array_fill($start, $actualLength, $value);
        $this->value = array_replace($this->value, $replacement);
        ksort($this->value);

        return $this;
    }

    # 배열 끝에 하나의 행을 추가합니다.
    public function append(array $args): self
    {
        $this->value[] = $args;
        return $this;
    }

    #
    # 숫자값의 합계를 반환합니다.
    # key가 null 또는 빈 문자열이면 현재 1차원 배열의 숫자값을 합산합니다.
    public function sum(string|int|null $key = null): int|float
    {
        $numbers = $this->findNumeric($this->normalizeOptionalKey($key));
        return array_sum($numbers);
    }

    # 숫자값의 최솟값을 반환하며 숫자값이 없으면 null입니다.
    public function min(string|int|null $key = null): int|float|null
    {
        $numbers = $this->findNumeric($this->normalizeOptionalKey($key));
        return $numbers === [] ? null : min($numbers);
    }

    # 숫자값의 최댓값을 반환하며 숫자값이 없으면 null입니다.
    public function max(string|int|null $key = null): int|float|null
    {
        $numbers = $this->findNumeric($this->normalizeOptionalKey($key));
        return $numbers === [] ? null : max($numbers);
    }

    # 숫자값의 평균을 반환하며 숫자값이 없으면 null입니다.
    public function avg(string|int|null $key = null): int|float|null
    {
        $numbers = $this->findNumeric($this->normalizeOptionalKey($key));
        if ($numbers === []) {
            return null;
        }

        return array_sum($numbers) / count($numbers);
    }

    #
    # 여러 데이터셋에서 선택한 열을 같은 행 위치끼리 합칩니다.
    #
    # value 예:
    # [
    #   [ ['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B'] ],
    #   [ ['score' => 90],            ['score' => 80] ]
    # ]
    #
    # params 예:
    # [0 => 'id,name', 1 => ['score']]
    public function union(array $params): self
    {
        $result = [];

        foreach ($this->value as $datasetKey => $dataset) {
            if (!array_key_exists($datasetKey, $params)) {
                continue;
            }
            if (!is_array($dataset)) {
                throw new \UnexpectedValueException(
                    sprintf('%s: 데이터셋 [%s]는 배열이어야 합니다.', __METHOD__, (string) $datasetKey)
                );
            }

            $columns = $this->normalizeColumnSelection($params[$datasetKey], __METHOD__);
            $rows = array_values($dataset);

            foreach ($rows as $rowIndex => $row) {
                if (!is_array($row)) {
                    throw new \UnexpectedValueException(
                        sprintf(
                            '%s: 데이터셋 [%s]의 %d번째 행은 배열이어야 합니다.',
                            __METHOD__,
                            (string) $datasetKey,
                            $rowIndex
                        )
                    );
                }

                foreach ($columns as $column) {
                    if (array_key_exists($column, $row)) {
                        $result[$rowIndex][$column] = $row[$column];
                    }
                }
            }
        }

        $this->value = array_values($result);
        return $this;
    }

    # 전달받은 배열들을 순서대로 이어 붙입니다.
    public function unionAll(array ...$params): self
    {
        $this->value = $params === [] ? [] : array_merge(...$params);
        return $this;
    }

    #
    # 지정 열에서 첫 번째 일치 행의 0 기반 위치를 반환합니다.
    # 찾지 못하면 -1입니다.
    public function findIndex(string|int $key, mixed $val, bool $strict = false): int
    {
        $this->assertRows($this->value, __METHOD__);

        $position = 0;
        foreach ($this->value as $row) {
            if (
                array_key_exists($key, $row)
                && $this->valuesEqual($row[$key], $val, $strict)
            ) {
                return $position;
            }
            $position++;
        }

        return -1;
    }

    # 배열을 length개씩 묶습니다.
    public function split(int $length = 2, bool $preserveKeys = false): self
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('split의 length는 1 이상이어야 합니다.');
        }

        $this->value = array_chunk($this->value, $length, $preserveKeys);
        return $this;
    }

    # 배열 일부를 잘라냅니다.
    public function slice(int $offset, ?int $length = null, bool $preserveKeys = false): self
    {
        $this->value = array_slice($this->value, $offset, $length, $preserveKeys);
        return $this;
    }

    #
    # 각 행의 키를 위치 기준으로 변경합니다.
    # 새 키가 부족하면 해당 행의 기존 키를 나머지 위치에 사용하고, 많으면 잘라냅니다.
    #
    # 사용 예: changeKeys('user_id', 'user_name'), changeKeys(['user_id', 'user_name'])
    public function changeKeys(mixed ...$keys): self
    {
        $newKeys = count($keys) === 1 && is_array($keys[0])
            ? array_values($keys[0])
            : $keys;
        $newKeys = $this->validateKeys($newKeys, __METHOD__);

        if ($this->value === []) {
            return $this;
        }

        $this->assertRows($this->value, __METHOD__);
        $result = [];

        foreach ($this->value as $rowIndex => $row) {
            $values = array_values($row);
            $valueCount = count($values);
            if ($valueCount === 0) {
                $result[] = [];
                continue;
            }

            $effectiveKeys = array_slice($newKeys, 0, $valueCount);
            $originalKeys = array_keys($row);

            for ($i = count($effectiveKeys); $i < $valueCount; $i++) {
                $effectiveKeys[] = $originalKeys[$i];
            }

            $this->assertUniqueKeys($effectiveKeys, __METHOD__, $rowIndex);
            $combined = array_combine($effectiveKeys, $values);
            if ($combined === false) {
                throw new \RuntimeException(__METHOD__ . ': 키 변경 중 array_combine에 실패했습니다.');
            }
            $result[] = $combined;
        }

        $this->value = $result;
        return $this;
    }

    # 각 요소에 callback을 적용하며 기존 배열 키를 보존합니다.
    public function map(callable $callback): self
    {
        $result = [];
        foreach ($this->value as $key => $item) {
            $result[$key] = $callback($item);
        }
        $this->value = $result;

        return $this;
    }

    # 배열을 하나의 값으로 축약합니다.
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->value, $callback, $initial);
    }

    #
    # 지정 열을 1차원 배열로 추출합니다.
    # indexKey를 지정하면 해당 열의 값을 결과 키로 사용합니다.
    public function pluck(string|int $key, string|int|null $indexKey = null): self
    {
        $this->assertRows($this->value, __METHOD__);
        $this->value = array_column($this->value, $key, $indexKey);
        return $this;
    }

    #
    # 기존 코드 호환용 읽기 접근입니다.
    # $helper->value와 $helper->origin만 허용합니다.
    public function __get(string $propertyName): mixed
    {
        return match ($propertyName) {
            'value' => $this->value,
            'origin' => $this->origin,
            default => throw new \OutOfBoundsException("정의되지 않은 속성입니다: {$propertyName}"),
        };
    }

    #
    # 기존 코드 호환용 쓰기 접근입니다.
    # value를 바꿔도 origin은 유지되며, 둘 다 바꾸려면 init()을 사용하세요.
    #
    # @param array<mixed> $args
    public function __set(string $propertyName, array $args): void
    {
        match ($propertyName) {
            'value' => $this->value = $args,
            'origin' => $this->origin = $args,
            default => throw new \OutOfBoundsException("정의되지 않은 속성입니다: {$propertyName}"),
        };
    }

    public function __isset(string $propertyName): bool
    {
        return $propertyName === 'value' || $propertyName === 'origin';
    }

    private function normalizeLogicalOperator(string $operator): string
    {
        $operator = strtoupper(trim($operator));
        if ($operator !== 'AND' && $operator !== 'OR') {
            throw new \InvalidArgumentException(
                "지원하지 않는 논리 연산자입니다: {$operator}. AND 또는 OR를 사용하세요."
            );
        }

        return $operator;
    }

    # @param array<string|int, mixed> $params
    private function validateWhereParams(array $params): void
    {
        foreach ($params as $field => $condition) {
            if (!is_array($condition)) {
                continue;
            }

            if (!array_key_exists(0, $condition)) {
                throw new \InvalidArgumentException(
                    sprintf('필드 [%s] 조건은 [연산자, 비교값] 형식이어야 합니다.', (string) $field)
                );
            }

            $comparison = strtoupper(trim((string) $condition[0]));
            if (!in_array($comparison, self::COMPARISON_OPERATORS, true)) {
                throw new \InvalidArgumentException(
                    "지원하지 않는 비교 연산자입니다: {$comparison}"
                );
            }

            $unary = $comparison === 'IS NULL' || $comparison === 'IS NOT NULL';
            $expectedKeys = $unary ? [0] : [0, 1];
            if (array_keys($condition) !== $expectedKeys) {
                $format = $unary ? "['{$comparison}']" : "['{$comparison}', 비교값]";
                throw new \InvalidArgumentException(
                    sprintf('필드 [%s] 조건은 %s 형식이어야 합니다.', (string) $field, $format)
                );
            }

            if ($unary) {
                continue;
            }

            $expected = $condition[1];
            if (in_array($comparison, ['IN', 'NOT IN', 'IN STRICT', 'NOT IN STRICT'], true)) {
                $this->requireArrayOperand($expected, $comparison);
            }

            if (in_array(
                $comparison,
                ['BETWEEN', 'NOT BETWEEN', 'BETWEEN EXCLUSIVE', 'NOT BETWEEN EXCLUSIVE'],
                true
            )) {
                $range = $this->requireArrayOperand($expected, $comparison);
                if (array_keys($range) !== [0, 1]) {
                    throw new \InvalidArgumentException(
                        "{$comparison} 비교값은 [최솟값, 최댓값] 형식이어야 합니다."
                    );
                }
            }
        }
    }

    #
    # @param array<string|int, mixed> $row
    # @param array<string|int, mixed> $params
    private function matchesWhere(array $row, array $params, string $operator): bool
    {
        if ($params === []) {
            return true;
        }

        foreach ($params as $field => $condition) {
            $matched = array_key_exists($field, $row)
                && $this->matchesCondition($row[$field], $condition);

            if ($operator === 'AND' && !$matched) {
                return false;
            }
            if ($operator === 'OR' && $matched) {
                return true;
            }
        }

        return $operator === 'AND';
    }

    private function matchesCondition(mixed $actual, mixed $condition): bool
    {
        if (!is_array($condition)) {
            // null은 빈 문자열과 구분하고, 그 외 기본 조건은 1.x 호환을 위해 느슨한 비교를 유지합니다.
            return $this->valuesEqual($actual, $condition, false);
        }

        $comparison = strtoupper(trim((string) $condition[0]));
        $expected = $condition[1] ?? null;

        return match ($comparison) {
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            '=', '==' => $this->valuesEqual($actual, $expected, false),
            '===' => $this->valuesEqual($actual, $expected, true),
            '!=', '<>' => !$this->valuesEqual($actual, $expected, false),
            '!==' => !$this->valuesEqual($actual, $expected, true),

            'LIKE', 'CONTAINS' => str_contains(
                $this->stringify($actual, $comparison),
                $this->stringify($expected, $comparison)
            ),
            'NOT LIKE', 'NOT CONTAINS' => !str_contains(
                $this->stringify($actual, $comparison),
                $this->stringify($expected, $comparison)
            ),
            'LIKE-R', 'STARTS WITH' => str_starts_with(
                $this->stringify($actual, $comparison),
                $this->stringify($expected, $comparison)
            ),
            'LIKE-L', 'ENDS WITH' => str_ends_with(
                $this->stringify($actual, $comparison),
                $this->stringify($expected, $comparison)
            ),

            'IN' => $this->containsValue(
                $this->requireArrayOperand($expected, $comparison),
                $actual,
                false
            ),
            'NOT IN' => !$this->containsValue(
                $this->requireArrayOperand($expected, $comparison),
                $actual,
                false
            ),
            'IN STRICT' => $this->containsValue(
                $this->requireArrayOperand($expected, $comparison),
                $actual,
                true
            ),
            'NOT IN STRICT' => !$this->containsValue(
                $this->requireArrayOperand($expected, $comparison),
                $actual,
                true
            ),

            'BETWEEN' => $this->isBetween($actual, $expected, true),
            'NOT BETWEEN' => !$this->isBetween($actual, $expected, true),
            'BETWEEN EXCLUSIVE' => $this->isBetween($actual, $expected, false),
            'NOT BETWEEN EXCLUSIVE' => !$this->isBetween($actual, $expected, false),

            'IS NULL' => $actual === null,
            'IS NOT NULL' => $actual !== null,

            default => throw new \InvalidArgumentException(
                "지원하지 않는 비교 연산자입니다: {$comparison}"
            ),
        };
    }

    # @param array<mixed> $values
    private function containsValue(array $values, mixed $needle, bool $strict): bool
    {
        foreach ($values as $value) {
            if ($this->valuesEqual($needle, $value, $strict)) {
                return true;
            }
        }

        return false;
    }

    private function valuesEqual(mixed $left, mixed $right, bool $strict): bool
    {
        if ($strict) {
            return $left === $right;
        }

        // PHP의 느슨한 비교에서 null과 빈 문자열이 같아지는 문제를 방지합니다.
        if ($left === null || $right === null) {
            return $left === null && $right === null;
        }

        // 서로 다른 복합 형식 비교 시 발생할 수 있는 객체 변환 Notice를 방지합니다.
        if (is_object($left) || is_object($right)) {
            return is_object($left) && is_object($right) && $left == $right;
        }
        if (is_array($left) || is_array($right)) {
            return is_array($left) && is_array($right) && $left == $right;
        }
        if (is_resource($left) || is_resource($right)) {
            return is_resource($left) && is_resource($right) && (int) $left === (int) $right;
        }

        return $left == $right;
    }

    private function stringify(mixed $value, string $operator): string
    {
        if ($value === null) {
            return '';
        }
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(
            sprintf('%s 연산에는 문자열로 변환 가능한 값만 사용할 수 있습니다.', $operator)
        );
    }

    # @return array<mixed>
    private function requireArrayOperand(mixed $expected, string $operator): array
    {
        if (!is_array($expected)) {
            throw new \InvalidArgumentException("{$operator} 연산의 비교값은 배열이어야 합니다.");
        }

        return $expected;
    }

    private function isBetween(mixed $actual, mixed $expected, bool $inclusive): bool
    {
        $range = $this->requireArrayOperand($expected, $inclusive ? 'BETWEEN' : 'BETWEEN EXCLUSIVE');
        if (array_keys($range) !== [0, 1]) {
            throw new \InvalidArgumentException('BETWEEN 비교값은 [최솟값, 최댓값] 형식이어야 합니다.');
        }

        [$minimum, $maximum] = [$range[0], $range[1]];
        if ($minimum > $maximum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        return $inclusive
            ? $actual >= $minimum && $actual <= $maximum
            : $actual > $minimum && $actual < $maximum;
    }

    #
    # @param array<mixed> $rows
    private function assertRows(array $rows, string $method): void
    {
        foreach ($rows as $key => $row) {
            if (!is_array($row)) {
                throw new \UnexpectedValueException(
                    sprintf('%s: [%s] 요소는 배열이어야 하며 실제 형식은 %s입니다.', $method, (string) $key, get_debug_type($row))
                );
            }
        }
    }

    #
    # @param array<mixed> $keys
    # @return array<int, string|int>
    private function validateKeys(array $keys, string $method): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (!is_string($key) && !is_int($key)) {
                throw new \InvalidArgumentException(
                    sprintf('%s: 키는 string 또는 int여야 하며 실제 형식은 %s입니다.', $method, get_debug_type($key))
                );
            }
            $result[] = $key;
        }

        return $result;
    }

    #
    # @param array<mixed> $params
    # @return array<int, string|int>
    private function normalizeKeyArguments(array $params, string $method): array
    {
        $keys = count($params) === 1 && is_array($params[0])
            ? array_values($params[0])
            : $params;

        return $this->validateKeys($keys, $method);
    }

    #
    # @param array<string|int, mixed> $row
    # @param array<int, string|int> $keys
    private function rowHasNullLikeValue(array $row, array $keys): bool
    {
        if ($keys === []) {
            foreach ($row as $value) {
                if ($value === null || $value === '') {
                    return true;
                }
            }
            return false;
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                return true;
            }
        }

        return false;
    }

    #
    # @return array<int, int|float>
    private function findNumeric(string|int|null $key): array
    {
        $result = [];

        if ($key === null) {
            foreach ($this->value as $value) {
                $number = $this->toNumber($value);
                if ($number !== null) {
                    $result[] = $number;
                }
            }
            return $result;
        }

        $this->assertRows($this->value, __METHOD__);
        foreach ($this->value as $row) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $number = $this->toNumber($row[$key]);
            if ($number !== null) {
                $result[] = $number;
            }
        }

        return $result;
    }

    private function toNumber(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            # @var int|float $number
            $number = $value + 0;
            return $number;
        }

        return null;
    }

    private function normalizeOptionalKey(string|int|null $key): string|int|null
    {
        return $key === '' ? null : $key;
    }

    #
    # @return array<int, string|int>
    private function normalizeColumnSelection(mixed $selection, string $method): array
    {
        if (is_string($selection)) {
            $selection = array_values(array_filter(
                array_map('trim', explode(',', $selection)),
                static fn(string $column): bool => $column !== ''
            ));
        }

        if (!is_array($selection)) {
            throw new \InvalidArgumentException(
                sprintf('%s: 열 선택값은 쉼표 문자열 또는 배열이어야 합니다.', $method)
            );
        }

        return $this->validateKeys(array_values($selection), $method);
    }

    #
    # @param array<int, string|int> $keys
    private function assertUniqueKeys(array $keys, string $method, string|int $rowIndex): void
    {
        $normalized = [];
        foreach ($keys as $key) {
            $normalized[$key] = true;
        }

        if (count($normalized) !== count($keys)) {
            throw new \InvalidArgumentException(
                sprintf('%s: [%s]번째 행에 적용할 키가 중복됩니다.', $method, (string) $rowIndex)
            );
        }
    }

    private function fingerprint(mixed $value): string
    {
        if ($value === null || is_scalar($value)) {
            return get_debug_type($value) . ':' . serialize($value);
        }
        if (is_resource($value)) {
            return 'resource:' . get_resource_type($value) . ':' . (int) $value;
        }
        if (is_object($value)) {
            return 'object:' . get_class($value) . ':' . spl_object_id($value);
        }

        $parts = [];
        foreach ($value as $key => $item) {
            $parts[] = serialize($key) . '=>' . $this->fingerprint($item);
        }
        return 'array:[' . implode('|', $parts) . ']';
    }

    # @param array<mixed> $array
    private function isList(array $array): bool
    {
        $expected = 0;
        foreach ($array as $key => $_value) {
            if ($key !== $expected) {
                return false;
            }
            $expected++;
        }
        return true;
    }
}
