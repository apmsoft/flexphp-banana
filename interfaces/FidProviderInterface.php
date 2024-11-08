<?php
namespace Flex\Banana\Interfaces;

interface FidProviderInterface
{
    public function getTable(): string;
    public function getFidColumnName(): string;
}