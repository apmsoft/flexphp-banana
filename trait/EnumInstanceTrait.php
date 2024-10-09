<?php
namespace Flex\Banana\Trait;

use Flex\Banana\Class\Enum\EnumValueStorage;

trait EnumInstanceTrait
{
    public static function create(): self
    {
        return self::cases()[0];
    }

    public function setValue(string $key, $value): void
    {
        EnumValueStorage::setValue(static::class, $key, $value);
    }

    public function getValue(string $key)
    {
        return EnumValueStorage::getValue(static::class, $key);
    }

    public static function resetValues(): void
    {
        EnumValueStorage::reset(static::class);
    }

    public function getInstanceValues(): array
    {
        return EnumValueStorage::getValues(static::class);
    }
}
?>