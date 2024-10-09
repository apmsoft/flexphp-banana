<?php
namespace Flex\Banana\Interface;

interface UpdateInterface{
    public function doUpdate(?array $params=[]) : ?string;
}
?>