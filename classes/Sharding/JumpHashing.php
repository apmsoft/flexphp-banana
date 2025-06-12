<?php
namespace Flex\Banana\Classes\Sharding;

use Flex\Banana\Interfaces\ShardingStrategyInterface;

final class JumpHashing implements ShardingStrategyInterface 
{
	private array $servers = [];

	public function addServer(string $group, string $server, int $weight = 1): void {
		if (!in_array($server, $this->servers, true)) {
			$this->servers[] = $server;
		}
	}

	public function removeServer(string $group, string $server): void {
		$this->servers = array_values(array_filter(
			$this->servers,
			fn($s) => $s !== $server
		));
	}

	public function getServer(string $group, string $key): ?string {
		$numServers = count($this->servers);
		if ($numServers === 0) {
			throw new \RuntimeException("No servers registered.");
		}

		$hash = $this->hash64($key);
		$b = -1;
		$j = 0;

		while ($j < $numServers) {
			$b = $j;
			$hash = ($hash * 2862933555777941757) + 1;
			$j = (int)(($b + 1) * (2147483648 / ((($hash >> 33) + 1))));
		}

		return $this->servers[$b];
	}

	private function hash64(string $key): int {
		return crc32($key) | 0x100000000; // 64비트 해시 흉내
	}
}