<?php
namespace Flex\Banana\Traits;

trait EntryArrayTrait
{
	public static function names(): array
	{
		return array_column(self::cases(), 'name');
	}

	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}

	public static function array(): array
	{
		if (count(self::names()) == count(self::values())) {
			return array_combine(self::names(), self::values());
		} else {
			return [];
		}
	}

	public static function byName(string $name, string $case = 'UPPER'): ?object
	{
		$NAME = ('UPPER' == strtoupper($case)) ? strtoupper($name) :
						(('LOWER' == strtoupper($case)) ? strtolower($name) : $name);

		foreach (self::cases() as $case) {
			if (strtoupper($case->name) === $NAME) {
				return (object)[
					'name'  => $case->name,
					'value' => $case->value
				];
			}
		}

		return null;
	}

	public static function __callStatic(string $name, array $args = []): mixed
  {
    // 기존 byName() 호출 로직
    $entry = self::byName($name);
    if ($entry) {
      return $entry->value;
    }

    // 단일 enum 케이스에서만 인스턴스 메서드 호출 허용
    if (count(self::cases()) === 1) {
			// EnumInstanceTrait 클래스와 함께 사용해야 함
			$enumInstance = self::cases()[0];
			
			// 호출하려는 메서드가 인스턴스에 존재하는지 확인
			if (method_exists($enumInstance, $name)) {
				return $enumInstance->$name(...$args);
			}
    }

    return null;
  }

	// 단일 enum 케이스용 메서드
	public static function name(): ?string
	{
		if (count(self::cases()) !== 1) {
			throw new \LogicException(static::class . '::name()은 단일 enum 케이스에서만 사용할 수 있습니다.');
    }
    return self::cases()[0]->name;
	}

	public static function value(): mixed
	{
		if (count(self::cases()) !== 1) {
			throw new \LogicException(static::class . '::value()은 단일 enum 케이스에서만 사용할 수 있습니다.');
		}
		return self::cases()[0]->value;
	}
}