<?php
namespace Flex\Banana\Interfaces;

interface DeleteInterface{
    public function doDelete(?array $params=[]) : ?string;
}