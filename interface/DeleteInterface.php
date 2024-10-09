<?php
namespace Flex\Banana\Interface;

interface DeleteInterface{
    public function doDelete(?array $params=[]) : ?string;
}
?>