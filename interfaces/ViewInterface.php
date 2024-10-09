<?php
namespace Flex\Banana\Interfaces;

interface ViewInterface{
    public function doView(?array $params=[]) : ?string;
}
?>