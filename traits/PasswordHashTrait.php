<?php
namespace Flex\Banana\Traits;

use Flex\Banana\Classes\Cipher\CipherGeneric;
use Flex\Banana\Classes\Cipher\PasswordHash;

trait PasswordHashTrait
{
    public function hashPassword(string $value): string
    {
        $passwordCipher = new CipherGeneric(new PasswordHash());
        return $passwordCipher->hash($value);
    }
}