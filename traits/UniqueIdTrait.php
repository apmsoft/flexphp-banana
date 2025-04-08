<?php
namespace Flex\Banana\Traits;

use Flex\Banana\Classes\Uuid\UuidGenerator;

# 각종 토큰 및 id 생성
trait UniqueIdTrait
{
    public function genId(): string
    {
        $uuid = (new UuidGenerator())->v7();
        return $uuid;
    }
}