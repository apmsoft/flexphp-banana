<?php
namespace Flex\Banana\Interfaces;

interface FidProviderInterface
{
    public function getTable(?string $table): string;
    public function getFidColumnName(?string $columnName): string;
}