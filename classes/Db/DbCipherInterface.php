<?php
namespace Flex\Banana\Classes\Db;
interface DbCipherInterface
{
    public function encrypt(string $value): string;
    public function decrypt(string $value): string;
}