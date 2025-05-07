<?php
namespace Flex\Banana\Adapters;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Log;

final class TaskJsonAdapter
{
    public const __version = '0.2.0';
    private array $workflow;

    public function __construct(array $workflow)
    {
        $this->workflow = $workflow;
    }

    public function process(TaskFlow $flow): TaskFlow
    {
        Log::d("JsonAdapter: 워크플로우 처리 시작.");
        foreach ($this->workflow as $step) {
            Log::d("JsonAdapter: 스텝 실행 중 - " . json_encode($step));
            match ($step['type'] ?? 'class') {
                'method' => self::handleMethodStep($flow, $step),
                'function' => self::handleFunctionStep($flow, $step),
                'class' => self::handleClassStep($flow, $step),
                default => throw new \Exception("Unknown step type: " . ($step['type'] ?? '')),
            };
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

        $flow->do(function(TaskFlow $task) use ($step, $resolve) {
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
        });
    }

    private static function resolveContextReference(TaskFlow $flow, $value)
    {
        if (is_string($value) && str_starts_with($value, '@')) {
            $parts = explode('.', substr($value, 1));
            $ctx = isset($flow->{$parts[0]}) ? $flow->{$parts[0]} : null;
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