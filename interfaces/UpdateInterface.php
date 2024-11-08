<?php
namespace Flex\Banana\Interfaces;

interface UpdateInterface{
    public function doUpdate(?array $params=[]) : ?string;
}