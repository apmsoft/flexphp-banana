<?php
namespace Flex\Banana\Interfaces;

interface EnumInterface
{
    public function filter(mixed $data = null, ...$params): mixed;
    public function format(mixed $data = null, ...$params): mixed;
    public function validate(mixed $data = null, ...$params): void;
}