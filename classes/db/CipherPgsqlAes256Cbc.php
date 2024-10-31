<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\DbCipherInterface;

class CipherPgsqlAes256Cbc implements DbCipherInterface
{
    public const __version = '0.1';
    public const ENCRYPTION_MODE = 'aes-cbc';
    public function __construct(
        private string $hashkey,
    ){}

    public function encrypt(string $column): string
    {
        return sprintf(
            "encode(encrypt(convert_to('%s', 'UTF8'), '".hash('sha256',$this->hashkey)."','".self::ENCRYPTION_MODE."'),'hex')",
            $column
        );
    }

    public function decrypt(string $column): string
    {
        return sprintf(
            "convert_from(decrypt(decode(%s, 'hex'), '".hash('sha256',$this->hashkey)."','".self::ENCRYPTION_MODE."'), 'UTF8')",
            $column
        );
    }
}