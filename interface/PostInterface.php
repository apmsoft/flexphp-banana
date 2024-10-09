<?php
namespace Flex\Banana\Interface;

interface PostInterface{
    public function doPost(?array $params=[]) : ?string;
}
?>