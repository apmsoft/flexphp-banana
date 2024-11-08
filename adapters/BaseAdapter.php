<?php
namespace Flex\Banana\Adapters;

use Flex\Banana\Interfaces\BaseAdapterInterface;

class BaseAdapter implements BaseAdapterInterface{
    public const __version = '0.1';

    public function getVersion(): string
    {
        return static::__version;
    }
}