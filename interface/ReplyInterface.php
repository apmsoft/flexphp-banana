<?php
namespace Flex\Banana\Interface;

interface ReplyInterface{
    public function doReply(?array $params=[]) : ?string;
}
?>