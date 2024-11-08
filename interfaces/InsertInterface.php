<?php
namespace Flex\Banana\Interfaces;

interface InsertInterface{
    public function doInsert(?array $params=[]) : ?string;
}