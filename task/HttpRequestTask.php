<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\Http\HttpRequest;
use Flex\Banana\Classes\Log;

class HttpRequestTask
{
    public const __version = '0.1.0';

    public function __construct(
    ) {}

    public function execute(string $method, array $set): mixed
    {
        try{
            $request = new HttpRequest();

            if (empty($set['url'])) {
                throw new \Exception("HttpRequestTask::execute - empty or invalid URL");
            }

            if(!is_string($set['params'])){
                throw new \Exception("HttpRequestTask::execute - 'params' must be string, got " . gettype($set['params']));
            }

            $headers = is_array($set['headers'] ?? null) ? $set['headers'] : [];
            $request->set($set['url'], $set['params'] ?? '', $headers);
            $responses = match(strtoupper($method)){
                "POST"   => $request->post(),
                "GET"    => $request->get(),
                "PUT"    => $request->put(),
                "PATCH"  => $request->patch(),
                "DELETE" => $request->delete(),
                default  => throw new \Exception("HttpRequestTask::execute - Unsupported method: {$method}")
            };

            return $responses;
        }catch(\Exception $e){
            Log::e("HttpRequestTask::execute error", [
                'message' => $e->getMessage(),
                'set' => $set,
                'method' => $method
            ]);
            throw new \Exception("HttpRequestTask::execute failed: " . $e->getMessage());
        }
    }
}