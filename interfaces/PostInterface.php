<?php
namespace Flex\Banana\Interfaces;

interface PostInterface{
    public function doPost(?array $params=[]) : ?string;
}
?>