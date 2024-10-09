<?php
namespace Flex\Banana\Interfaces;

interface ListInterface{
    public function doList(?array $params=[]) : ?string;
}
?>