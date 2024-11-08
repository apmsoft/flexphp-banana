<?php
namespace Flex\Banana\Interfaces;

interface EditInterface{
    public function doEdit(?array $params=[]) : ?string;
}