<?php
namespace Flex\Banana\Interfaces;

interface EnumInterface
{
    public function filter(?mixed $data, ...$params): mixed;
    public function format(?mixed $data, ...$params): mixed;
    public function validate(?mixed $data, ...$params): void;
}