<?php
namespace Flex\Banana\Interface;

interface ReplInterface{
    public function doRepl(?array $params=[]) : ?string;
}
?>