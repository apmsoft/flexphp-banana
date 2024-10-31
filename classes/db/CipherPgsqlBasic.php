<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\DbCipherInterface;

class CipherPgsqlBasic implements DbCipherInterface
{
    public const __version = '0.1';
    public function __construct(
    ){}

    public function encrypt(string $column): string
    {
        return sprintf(
            "encode(convert_to('%s', 'UTF8'), 'hex')",
            $column
        );
    }

    public function decrypt(string $column): string
    {
        return sprintf(
            "convert_from(decode(%s, 'hex'), 'UTF8')",
            $column
        );
    }
}