<?php
namespace Flex\Banana\Interface;

interface ViewInterface{
    public function doView(?array $params=[]) : ?string;
}
?>