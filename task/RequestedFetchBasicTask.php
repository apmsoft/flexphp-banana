<?php
namespace Flex\Banana\Task;

use Flex\Banana\Utils\Requested;

class RequestedFetchBasicTask
{
    public const __version = '0.1.0';

    public function __construct(
        private Requested $requested
    ){}

    private function postFetch() : array
    {
        return $this->requested->post()->fetch();
    }

    private function getFetch() : array
    {
        return $this->requested->get()->fetch();
    }

    public function execute(string $method) : array
    {
        $methodCase = strtoupper($method);
        return match($methodCase) {
            "POST" => $this->postFetch(),
            "GET"=> $this->getFetch(),
            default => throw new \InvalidArgumentException("Invalid method: {$method}"),
        };
    }
}