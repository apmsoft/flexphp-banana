<?php
namespace Flex\Banana\Classes\Sharding;

use Flex\Banana\Interfaces\ShardingStrategyInterface;

final class ShardManager {
	private static array $strategies = [];

	public static function use(string $group, ShardingStrategyInterface $strategy): void {
    self::$strategies[$group] = $strategy;
	}

	public static function addServer(string $group, string $server): void {
		self::strategy($group)->addServer($group, $server);
	}

	public static function removeServer(string $group, string $server): void {
		self::strategy($group)->removeServer($group, $server);
	}

	public static function getServer(string $group, string $key): ?string {
		return self::strategy($group)->getServer($group, $key);
	}

	private static function strategy(string $group): ShardingStrategyInterface {
		if (!isset(self::$strategies[$group])) {
			throw new \RuntimeException("No strategy defined for group: {$group}");
		}
		return self::$strategies[$group];
	}
}