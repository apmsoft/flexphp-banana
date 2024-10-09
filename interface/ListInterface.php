<?php
namespace Flex\Banana\Interface;

interface ListInterface{
    public function doList(?array $params=[]) : ?string;
}
?>