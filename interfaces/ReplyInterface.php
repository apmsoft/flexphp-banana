<?php
namespace Flex\Banana\Interfaces;

interface ReplyInterface{
    public function doReply(?array $params=[]) : ?string;
}