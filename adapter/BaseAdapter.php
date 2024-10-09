<?php
namespace Flex\Banana\Adapter;

use Flex\Banana\Interface\BaseAdapterInterface;

class BaseAdapter implements BaseAdapterInterface{
    public const __version = '0.1';

    public function getVersion(): string
    {
        return static::__version;
    }
}

?>