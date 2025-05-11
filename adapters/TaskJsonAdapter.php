<?php
namespace Flex\Banana\Adapters;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Log;

final class TaskJsonAdapter
{
    public const __version = '0.5.0';
    private array $workflow;

    public function __construct(array $workflow)
    {
        $this->workflow = $workflow;
    }

    public function process(TaskFlow $flow): TaskFlow
    {
        Log::d("JsonAdapter: 워크플로우 처리 시작.");

        $index = 0;
        $count = count($this->workflow);

        // idMap: id => step (for fast lookup)
        $idMap = [];
        foreach ($this->workflow as $step) {
            if (isset($step['id'])) {
                $idMap[$step['id']] = $step;
            }
        }

        while ($index < $count)
        {
            $step = $this->workflow[$index];
            Log::d("JsonAdapter: 스텝 실행 중 - " . json_encode($step));

            try {
                $type = $step['type'] ?? 'class';

                // if 조건 분기 처리
                if ($type === 'if')
                {
                    $condition = self::resolveContextReference($flow, $step['condition'] ?? '');
                    $outputs = $step['outputs'] ?? [];

                    $nextId = $outputs[$condition] ?? ($outputs['default'] ?? null);
                    if ($nextId && isset($idMap[$nextId])) {
                        // id = $ nextid로 단계로 이동하십시오
                        foreach ($this->workflow as $i => $s) {
                            if (isset($s['id']) && $s['id'] === $nextId) {
                                $index = $i;
                                continue 2;
                            }
                        }
                        throw new \Exception("Invalid or missing next step id: " . json_encode($nextId));
                    } else {
                        throw new \Exception("Invalid or missing next step id: " . json_encode($nextId));
                    }
                }

                // 일반 실행 처리
                match ($type) {
                    'method' => self::handleMethodStep($flow, $step),
                    'function' => self::handleFunctionStep($flow, $step),
                    'class' => self::handleClassStep($flow, $step),
                    default => throw new \Exception("Unknown step type: " . $type),
                };

                // go 지정 시 다음 id로 점프
                if (isset($step['go'])) {
                    $nextId = $step['go'];
                    if (isset($idMap[$nextId])) {
                        foreach ($this->workflow as $i => $s) {
                            if (isset($s['id']) && $s['id'] === $nextId) {
                                $index = $i;
                                continue 2;
                            }
                        }
                        throw new \Exception("Go step id '{$nextId}' not found");
                    } else {
                        throw new \Exception("Go step id '{$nextId}' not found");
                    }
                }

            } catch (\Throwable $e) {
                Log::e("JsonAdapter: 스텝 처리 중 예외 발생 - ", $e->getMessage());
                throw new \Exception($e->getMessage());
            }

            $index++;
        }

        Log::d("JsonAdapter: 워크플로우 처리 완료.");
        return $flow;
    }

    private static function handleMethodStep(TaskFlow $flow, array $step): void
    {
        $resolve = fn($v) => self::resolveContextReference($flow, $v);

        $objectName = $step['object'];
        $method     = $step['method'];
        $params     = array_map($resolve, $step['params'] ?? []);
        $target     = $flow->$objectName ?? null;

        Log::d("handleMethodStep: {$objectName}->{$method} 호출 중, 파라미터: " . json_encode($params));

        if (!is_object($target) || (!is_callable([$target, $method]) && !method_exists($target, '__call'))) {
            throw new \Exception("Method {$method} not callable on object {$objectName}");
        }

        $result = call_user_func_array([$target, $method], $params);

        Log::d("handleMethodStep: 결과: " . json_encode($result));

        foreach (($step['outputs'] ?? []) as $ctxKey => $resultKey) {
            $flow->$ctxKey = $resultKey === 'self' ? $target : ($resultKey === '@return' ? $result : null);
        }
    }

    private static function handleFunctionStep(TaskFlow $flow, array $step): void
    {
        $resolve = function($v) use (&$resolve, $flow) {
            if (is_array($v)) {
                return array_map($resolve, $v);
            }
            return self::resolveContextReference($flow, $v);
        };

        $function = $step['function'] ?? null;
        if (!$function || !is_callable($function)) {
            throw new \Exception("Function '{$function}' not callable.");
        }

        $params = array_map($resolve, $step['params'] ?? []);

        Log::d("handleFunctionStep: 함수 {$function} 호출 중, 파라미터: " . json_encode($params));

        $result = call_user_func_array($function, $params);

        Log::d("handleFunctionStep: 결과: " . json_encode($result));

        // Support extracting a property from the result object if 'property' is set
        if (!empty($step['property']) && is_object($result) && property_exists($result, $step['property'])) {
            $result = $result->{$step['property']};
        }

        foreach (($step['outputs'] ?? []) as $ctxKey => $resultKey) {
            if ($resultKey === '@return') {
                $flow->$ctxKey = $result;
            } elseif (is_string($resultKey) && str_starts_with($resultKey, '@')) {
                $resolvedKey = substr($resultKey, 1);
                $flow->$ctxKey = $flow->$resolvedKey ?? null;
            } elseif (is_array($result) && isset($result[$resultKey])) {
                $flow->$ctxKey = $result[$resultKey];
            }
        }
    }

    private static function handleClassStep(TaskFlow $flow, array $step): void
    {
        $resolve = function($v) use (&$resolve, $flow) {
            if (is_array($v)) {
                return array_map($resolve, $v);
            }
            return self::resolveContextReference($flow, $v);
        };

        try {
            $flow->do(function(TaskFlow $task) use ($step, $resolve) {
                try {
                    $class  = $step['class'] ?? null;
                    $method = $step['method'] ?? null;

                    if ($class && $class[0] === '@') {
                        $class = $task->{substr($class, 1)} ?? null;
                    }

                    if (!class_exists($class)) {
                        throw new \Exception("Class {$class} not found");
                    }

                    $constructArgs = [];
                    if (!empty($step['inputs']['@construct'])) {
                        $constructArgs = array_map($resolve, $step['inputs']['@construct']);
                        unset($step['inputs']['@construct']);
                    }

                    Log::d("handleClassStep: {$class} 인스턴스 생성 중, 생성자 인자: " . json_encode($constructArgs));

                    $instance = new $class(...$constructArgs);
                    $params = array_map($resolve, $step['params'] ?? []);

                    if (!empty($step['inputs'])) {
                        foreach ($step['inputs'] as $key => $ref) {
                            if (isset($task->$ref)) {
                                $params[$key] = $task->$ref;
                            }
                        }
                    }

                    Log::d("handleClassStep: {$class}->{$method} 호출 중, 파라미터: " . json_encode($params));

                    $result = call_user_func_array([$instance, $method], $params);

                    if (!empty($step['property'])) {
                        if (is_object($result) && property_exists($result, $step['property'])) {
                            $result = $result->{$step['property']};
                        } elseif (is_array($result) && isset($result[$step['property']])) {
                            $result = $result[$step['property']];
                        } elseif (is_object($instance) && property_exists($instance, $step['property'])) {
                            $result = $instance->{$step['property']};
                        }
                    }

                    Log::d("handleClassStep: 결과: " . json_encode($result));

                    // @return 해상도에 사용되는 경우 컨텍스트로 해결 된 결과를 주입.
                    foreach ($step['outputs'] ?? [] as $ctxKey => $resultKey) {
                        if (is_string($resultKey) && str_starts_with($resultKey, '@')) {
                            $resolvedKey = substr($resultKey, 1);
                            if (isset($task->{$resolvedKey})) {
                                $step['outputs'][$ctxKey] = $task->{$resolvedKey};
                            }
                        }
                    }

                    foreach (($step['outputs'] ?? []) as $ctxKey => $resultKey) {
                        if ($resultKey === '@class') {
                            $task->{$ctxKey} = $instance;
                        } elseif ($resultKey === '@return') {
                            $task->{$ctxKey} = $result;
                        } elseif (is_array($result) && isset($result[$resultKey])) {
                            $task->{$ctxKey} = $result[$resultKey];
                        }
                    }

                    return $task;
                } catch (\Throwable $e) {
                    Log::e($e->getMessage());
                    throw new \Exception($e->getMessage());
                }
            });
        } catch (\Throwable $e) {
            Log::e("handleClassStep 오류: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    private static function resolveContextReference(TaskFlow $flow, $value)
    {
        // @task 클래스 참조
        if ($value === '@task' || $value === '@flow') {
            return $flow;
        }

        if (is_string($value) && str_starts_with($value, '@')) 
        {
            // @enums::EnumName()
            if (preg_match('/^@([^:]+)::([^(]+)\(\)$/', $value, $matches)) {
                [$_, $ctxKey, $enumKey] = $matches;
                $ctx = $flow->$ctxKey ?? null;
                if (is_array($ctx) && isset($ctx[$enumKey]) && $ctx[$enumKey] instanceof \BackedEnum) {
                    return $ctx[$enumKey]->value;
                }
            }

            // @enums::EnumName || @object::property
            $parts = explode('::', substr($value, 1), 2);
            if (count($parts) === 2) {
                [$ctxKey, $enumKey] = $parts;
                $ctx = $flow->$ctxKey ?? null;
                if (is_array($ctx) && array_key_exists($enumKey, $ctx)) {
                    $resolved = $ctx[$enumKey];
                    if (is_string($resolved) && enum_exists($resolved)) {
                        return $resolved::cases()[0];
                    }
                    if ($resolved instanceof \UnitEnum) {
                        return $resolved;
                    }
                    return $resolved;
                }
            }

            // @object.property.subkey
            $parts = explode('.', substr($value, 1));
            $ctx = $flow->{$parts[0]} ?? null;
            if ($ctx === null) {
                Log::w("resolveContextReference: '{$value}' → null (not found)");
            }
            foreach (array_slice($parts, 1) as $key) {
                if ($ctx === null) {
                    Log::w("resolveContextReference: '{$value}' → null (not found)");
                    break;
                }
                $ctx = is_array($ctx) && isset($ctx[$key]) ? $ctx[$key] : null;
                if ($ctx === null) {
                    Log::w("resolveContextReference: '{$value}' → null (not found)");
                }
            }
            return $ctx;
        }
        return $value;
    }
}