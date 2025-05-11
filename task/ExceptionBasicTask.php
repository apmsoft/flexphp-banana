<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Log;

class ExceptionBasicTask
{
    public const __version = '0.1.0';

    public function __construct(
    ){}

    public function execute(string|array $message = '예외가 발생했습니다.'): void
    {
        if (is_array($message)) {
            $message = JsonEncoder::toJson($message);
        }
        Log::e("[ExceptionBasicTask]", $message);
        throw new \Exception($message);
    }
}