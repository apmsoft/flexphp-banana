<?php
namespace Flex\Banana\Adapters;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Log;

final class TaskJsonAdapter
{
  public const __version = '0.8.0';
  private array $workflow;
  private array $bannedEnvVars = [];
  private array $bannedConstants = [];

  // 무한루프 처리값 설정
  private const MAX_EXECUTION_STEPS = 100; 

  public function __construct(array $workflow)
  {
    $this->workflow = $workflow;
  }

  // 금지할 환경 변수 목록을 배열로 한번에 설정하는 메소드
  public function setBannedEnvVars(array $bannedVars): self
  {
    $this->bannedEnvVars = $bannedVars;
    return $this;
  }

  // 금지할 상수 목록을 배열로 한번에 설정하는 메소드
  public function setBannedConstants(array $bannedConstants): self
  {
    $this->bannedConstants = $bannedConstants;
    return $this;
  }

  public function process(TaskFlow $flow): TaskFlow
  {
    Log::d("====> JsonAdapter: 워크플로우 처리 시작.");

    $index = 0;
    $count = count($this->workflow);

    // SECURITY: DoS 방지를 위한 스텝 카운터 초기화
    $stepCounter = 0;

    // idMap: id => step index (for fast lookup)
    $idMap = [];
    foreach ($this->workflow as $i => $step) {
      if (isset($step['id'])) {
        $idMap[$step['id']] = $i;
      }
    }

    while ($index < $count)
    {
      $step = $this->workflow[$index];
      Log::d("JsonAdapter: 스텝 실행 중 - " . json_encode($step));

      // 무한루프 처리
      if (++$stepCounter > self::MAX_EXECUTION_STEPS) {
        throw new \Exception("***최대 실행 스텝(" . self::MAX_EXECUTION_STEPS . "회)을 초과***");
      }

      try {
        $type = $step['type'] ?? 'class';

        // switch/if 조건 분기 처리
        if ($type === 'switch' || $type === 'if') {
          $newIndex = $this->handleConditionalStep($flow, $step, $idMap);
          if ($newIndex !== null) {
            $index = $newIndex;
            continue;
          }
        }

        // 일반 실행 처리
        match ($type) {
          'method' => $this->handleMethodStep($flow, $step),
          'function' => $this->handleFunctionStep($flow, $step),
          'class' => $this->handleClassStep($flow, $step),
          default => throw new \Exception("Unknown step type: " . $type),
        };

        $newIndex = $this->handleGoStep($step, $idMap);
        if ($newIndex !== null) {
          $index = $newIndex;
          continue;
        }
      } catch (\Throwable $e) {
        Log::e("JsonAdapter: 스텝 처리 중 예외 발생 - ", $e->getMessage());
        throw new \Exception($e->getMessage());
      }

    $index++;
    }

    Log::d("<---- JsonAdapter: 워크플로우 처리 완료".PHP_EOL);
    return $flow;
  }

  private function handleMethodStep(TaskFlow $flow, array $step): void
  {
    $resolve = fn($v) => $this->resolveContextReference($flow, $v);

    $objectName = $step['object'];
    $method     = $step['method'];
    $params     = array_map($resolve, $step['params'] ?? []);

    if (str_starts_with($objectName, 'enum::')) {
      $enumKey = substr($objectName, strlen('enum::'));

      if (enum_exists($enumKey)) {
        $target = $enumKey::cases()[0];
      }
      // 기본 네임 스페이스 폴백
      elseif (enum_exists("\\Columns\\{$enumKey}")) {
          $target = ("\\Columns\\{$enumKey}")::cases()[0];
      } else {
        throw new \Exception("Enum class {$enumKey} not found");
      }
    } else {
      $target = $flow->$objectName ?? null;
    }
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

  private function handleFunctionStep(TaskFlow $flow, array $step): void
  {
    $resolve = function($v) use (&$resolve, $flow) {
      if (is_array($v)) {
        return array_map($resolve, $v);
      }
      return $this->resolveContextReference($flow, $v);
    };

    $function = $step['function'] ?? null;
    if (!$function) {
      throw new \Exception("Function '{$function}' not callable.");
    }

    // 열거적인 방법 호출 지원
    if (!empty($step['method']) && str_contains($function, '::')) {
      [$cls, $case] = explode('::', $function);
      if (enum_exists($cls)) {
        $enumInstance = constant("{$cls}::{$case}");
        $method = $step['method'];
        if (method_exists($enumInstance, $method)) {
          $params = array_map($resolve, $step['params'] ?? []);
          $result = call_user_func_array([$enumInstance, $method], $params);
          Log::d("handleFunctionStep (enum): {$cls}::{$case}->{$method} 호출 결과: " . json_encode($result));
          foreach (($step['outputs'] ?? []) as $ctxKey => $resultKey) {
              $flow->$ctxKey = $resultKey === '@return' ? $result : null;
          }
          return;
        } else {
          throw new \Exception("Method {$method} not found on enum {$cls}");
        }
      }
    }

    if (!is_callable($function)) {
      throw new \Exception("Function '{$function}' not callable.");
    }

    $params = array_map($resolve, $step['params'] ?? []);
    Log::d("handleFunctionStep: 함수 {$function} 호출 중, 파라미터: " . json_encode($params));

    // 변수 참조해야 하는 함수들  지원 (확장됨)
    if (in_array($function, ['array_push', 'array_unshift', 'array_shift', 'array_pop', 'sort', 'rsort', 'asort', 'ksort', 'usort', 'array_reverse'], true)) {
      if (!empty($params) && is_array($params[0])) {
        $ref = &$params[0];  // 참조

        if (in_array($function, ['sort', 'rsort', 'asort', 'ksort', 'usort'], true)) {
          $function($ref);
          $result = $ref;
        } else {
          $result = $function($ref);
        }

        // 이 라인 중요: 결과 배열을 다시 flow에 반영
        foreach (($step['outputs'] ?? []) as $ctxKey => $resultKey) {
          if ($resultKey === '@return') {
            $flow->$ctxKey = $result;
          } elseif ($resultKey === 'self') {
            $flow->$ctxKey = $ref;  // 참조 배열로 덮어쓰기
          }
        }
      } else {
          throw new \Exception("{$function} requires the first parameter to be an array.");
      }
    } else {
      $result = call_user_func_array($function, $params);
    }
    Log::d("handleFunctionStep: 결과: " . json_encode($result));

    // '속성'이 설정된 경우 결과 오브젝트에서 속성 추출 지원
    if (!empty($step['property']) && is_object($result) && property_exists($result, $step['property'])) {
      $result = $result->{$step['property']};
    }

    foreach (($step['outputs'] ?? []) as $ctxKey => $resultKey) {
      if ($resultKey === '@return') {
        // 항상 컨텍스트에 최신 결과 반영
        $flow->$ctxKey = $result;
      } elseif (is_string($resultKey) && str_starts_with($resultKey, '@')) {
        $resolvedKey = substr($resultKey, 1);
        $flow->$ctxKey = $flow->$resolvedKey ?? null;
      } elseif (is_array($result) && isset($result[$resultKey])) {
        $flow->$ctxKey = $result[$resultKey];
      }
    }
  }

  private function handleClassStep(TaskFlow $flow, array $step): void
  {
    $resolve = function($v) use (&$resolve, $flow) {
        if (is_array($v)) {
          return array_map($resolve, $v);
        }
        return $this->resolveContextReference($flow, $v);
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
          } elseif (!empty($step['inputs']['construct'])) {
            $constructArgs = array_map($resolve, $step['inputs']['construct']);
            unset($step['inputs']['construct']);
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

  private function resolveContextReference(TaskFlow $flow, $value)
  {
    if ($value === '@task') {
      return $flow;
    }

    if (is_string($value) && str_starts_with($value, '@'))
    {
      $ref = substr($value, 1);

      // ENV::
      if (str_starts_with($ref, 'ENV::')) {
        $envVar = substr($ref, 5);
        // SECURITY CHECK: 금지된 환경 변수인지 확인
        if (in_array($envVar, $this->bannedEnvVars, true)) {
            throw new \Exception("보안 오류: 금지된 환경 변수('@ENV::{$envVar}')에 대한 접근이 차단되었습니다.");
        }
        return getenv($envVar) ?: null;
      }

      // DEFINE::
      if (str_starts_with($ref, 'DEFINE::')) {
        $const = substr($ref, 8);
        // SECURITY CHECK: 금지된 상수인지 확인
        if (in_array($const, $this->bannedConstants, true)) {
            throw new \Exception("보안 오류: 금지된 상수('@DEFINE::{$const}')에 대한 접근이 차단되었습니다.");
        }
        return defined($const) ? constant($const) : null;
      }

      // R::method.key1.key2
      if (preg_match('/^R::([a-zA-Z_]+)((?:\.[a-zA-Z0-9_]+)*)$/', $ref, $match)) {
        $method = $match[1];
        $path = ltrim($match[2], '.');
        $args = explode('.', $path);
        $base = call_user_func(['\\Flex\\Banana\\Classes\\R', $method], $args[0] ?? null);

        foreach (array_slice($args, 1) as $key) {
          if (is_array($base) && array_key_exists($key, $base)) {
            $base = $base[$key];
          } else {
            return null;
          }
        }
        return $base;
      }

      // @enums::EnumName()
      if (preg_match('/^([^:]+)::([^(]+)\(\)$/', $ref, $matches)) {
        [$_, $ctxKey, $enumKey] = $matches;
        $ctx = $flow->$ctxKey ?? null;
        if (is_array($ctx) && isset($ctx[$enumKey]) && $ctx[$enumKey] instanceof \BackedEnum) {
          return $ctx[$enumKey]->value;
        }
      }

      // @enums::EnumName || @object::property
      $parts = explode('::', $ref, 2);
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
      $parts = explode('.', $ref);
      $ctx = $flow->{$parts[0]} ?? null;
      if ($ctx === null) {
          Log::w("resolveContextReference:", $value, "→ null (not found)");
      }
      foreach (array_slice($parts, 1) as $key) {
        if ($ctx === null) {
            Log::w("resolveContextReference:", $value, "→ null (not found)");
            break;
        }
        $ctx = is_array($ctx) && isset($ctx[$key]) ? $ctx[$key] : null;
        if ($ctx === null) {
            Log::w("resolveContextReference:", $value, "→ null (not found)");
        }
      }
      return $ctx;

      // @enum::IdEnum.value 또는 @enum::IdEnum()
      if (preg_match('/^enum::([a-zA-Z0-9_\\\\]+)(?:\.(\w+))?$/', $ref, $match)) {
        $enumClass = $match[1];
        $enumProp = $match[2] ?? null;

        // 네임스페이스 보완
        if (!enum_exists($enumClass) && enum_exists("\\Columns\\{$enumClass}")) {
            $enumClass = "\\Columns\\{$enumClass}";
        }

        if (!enum_exists($enumClass)) {
            Log::w("resolveContextReference:", $ref, "→ enum class not found");
            return null;
        }

        $enumInstance = $enumClass::cases()[0];

        if ($enumProp === 'value') {
            return $enumInstance->value;
        } elseif ($enumProp && method_exists($enumInstance, $enumProp)) {
            return $enumInstance->{$enumProp}(); // 메서드 실행
        } elseif ($enumProp) {
            return $enumInstance->{$enumProp} ?? null; // 속성
        }

        return $enumInstance; // 기본 객체 반환
      }
    }

    return $value;
  }

  private function handleConditionalStep(TaskFlow $flow, array $step, array $idMap): ?int
  {
    $condition = (string) $this->resolveContextReference($flow, $step['condition'] ?? '');
    $outputs = $step['outputs'] ?? [];
    Log::d('condition',$condition);
    // print_r($outputs);

    // 🔐 명시적으로 키 존재 여부 확인
    if (!array_key_exists($condition, $outputs) && !array_key_exists('default', $outputs)) {
        throw new \Exception("조건 {$condition}에 해당하는 분기 또는 기본(default) 분기가 없습니다.");
    }

    $nextId = $outputs[$condition] ?? $outputs['default'];
    Log::d('nextId',$nextId);
    if ($nextId && isset($idMap[$nextId])) {
        $nextIndex = $idMap[$nextId];
        return $nextIndex;
    }

    throw new \Exception("다음 단계 ID가 유효하지 않음: " . json_encode($nextId));
  }

  private function handleGoStep(array $step, array $idMap): ?int
  {
    if (!array_key_exists('go', $step) || $step['go'] === null || $step['go'] === '') {
      return null;
    }

    $nextId = $step['go'];
    if (isset($idMap[$nextId])) {
      $nextIndex = $idMap[$nextId];
      return $nextIndex;
    } else {
      throw new \Exception("Go step id '{$nextId}' not found");
    }
  }
}
