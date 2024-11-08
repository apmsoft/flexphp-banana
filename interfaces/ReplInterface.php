<?php
namespace Flex\Banana\Interfaces;

interface ReplInterface{
    public function doRepl(?array $params=[]) : ?string;
}