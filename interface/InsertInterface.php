<?php
namespace Flex\Banana\Interface;

interface InsertInterface{
    public function doInsert(?array $params=[]) : ?string;
}
?>