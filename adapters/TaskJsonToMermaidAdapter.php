<?php
namespace Flex\Banana\Adapters;

use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Log;

final class TaskJsonToMermaidAdapter
{
  public const __version = '0.3.0';
  private array $workflow;

  public function __construct(array $workflow)
  {
    $tasks = [];
    if (isset($workflow['tasks']) && is_array($workflow['tasks'])) {
        $tasks = $workflow['tasks'];
    } else {
        $tasks = $workflow;
    }

    if (empty($tasks)) {
      throw new \Exception("Mermaid로 변환할 Task 데이터가 없습니다.");
    }
    
    $this->workflow = $tasks;
  }

  public function process(): string
  { 
    $nodes = [];
    $edges = [];
    $taskCount = count($this->workflow);

    // 노드(작업) 정의
    foreach ($this->workflow as $index => $task) 
    {
      $taskId = !empty($task['id']) ? $task['id'] : "step{$index}";
      $title = htmlspecialchars($task['title'] ?? $taskId, ENT_QUOTES, 'UTF-8');
      $taskType = $task['type'] ?? null;

      if ($taskType === 'if' || $taskType === 'switch') {
        $nodes[] = "    {$taskId}{{\"{$title}\"}}";
      } else {
        $nodes[] = "    {$taskId}[\"{$title}\"]";
      }
    }

    // 연결(엣지) 정의
    foreach ($this->workflow as $index => $task) 
    {
      $currentId = !empty($task['id']) ? $task['id'] : "step{$index}";
      $taskType    = $task['type'] ?? null;
      $isException = isset($task['class']) && str_contains($task['class'], 'ExceptionBasicTask');

      if ($taskType === 'if' || $taskType === 'switch') 
      {
        if (isset($task['outputs']) && is_array($task['outputs'])) {
          foreach ($task['outputs'] as $condition => $target) {
            // [수정] target 값도 비어있지 않은지 확인
            if(!empty($target)) {
                $label = $condition === 'default' ? 'else' : $condition;
                $edges[] = "    {$currentId} -- \"{$label}\" --> {$target}";
            }
          }
        }
      } 
      // [수정] 'go'의 값이 존재하는지와 비어있지 않은지를 함께 확인
      elseif (isset($task['go']) && !empty($task['go'])) { 
        $edges[] = "    {$currentId} --> {$task['go']}";
      } 
      elseif ($isException || $index === $taskCount - 1) {
        // 종료 노드는 연결 없음
      } else {
        $nextIndex = (int)$index + 1;
        if (isset($this->workflow[$nextIndex])) {
          $nextTask = $this->workflow[$nextIndex];
          $nextId = !empty($nextTask['id']) ? $nextTask['id'] : 'step' . $nextIndex;
          $edges[] = "    {$currentId} --> {$nextId}";
        }
      }
    }

    // 최종 Mermaid 문자열 조합
    $mermaidParts = ["graph TD"];
    $mermaidParts = array_merge($mermaidParts, $nodes, array_unique($edges));
    
    return implode("\n", $mermaidParts);
  }
}