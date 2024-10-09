<?php
namespace Flex\Banana\Enum;

use Flex\Banana\Interface\EnumValueInterface;
use Flex\Banana\Trait\EntryArrayTrait;
use Flex\Banana\Trait\EnumInstanceTrait;

enum ExampleEnum: string implements EnumValueInterface
{
    use EnumInstanceTrait;
    use EntryArrayTrait;

    use ExampleEnumTypesTrait;

    case ID       = 'id';
    case TITLE    = 'title';
    case SIGNDATE = 'signdate';
    case FID      = 'fid';
}
?>