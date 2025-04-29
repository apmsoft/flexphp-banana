<?php
namespace Flex\Banana\Classes\Json;

final class JsonDecoder
{
    public const __version = '0.1.0';

    # 문자열 또는 중첩 문자열을 json 배열로 변환
    final public static function toArray(string $value, bool $assoc = true, int $depth = 512, bool $throwExceptionOnError = false) : array
    {
        do {
            $decoded = json_decode($value, $assoc, $depth);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($throwExceptionOnError) {
                    throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
                }
                return $value;
            }
            $value = $decoded;
        } while (is_string($value));

        return $value;
    }
}