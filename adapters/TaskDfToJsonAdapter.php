<?php
namespace Flex\Banana\Adapters;

use DOMDocument;
use DOMXPath;
use Flex\Banana\Classes\TaskFlow;
use Flex\Banana\Classes\Log;

final class TaskDfToJsonAdapter
{
    public const __version = '0.1.0';
    public function __construct(
        private TaskFlow $task
    ) {}

    public function execute(array $posts): array
    {
        $result = [];
        $flow_json = json_decode($posts['contents'],true);
        // print_r($flow_json);
        foreach ($flow_json as $node) {
            if (!isset($node['data'])) continue;
            $result[] = $node['data'];
        }
        // print_r($result);

        $posts["flow"] = json_encode($result,JSON_UNESCAPED_UNICODE);
        return $posts;
    }
}
