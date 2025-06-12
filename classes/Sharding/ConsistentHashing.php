<?php
namespace Flex\Banana\Classes\Sharding;

use Flex\Banana\Interfaces\ShardingStrategyInterface;

final class ConsistentHashing implements ShardingStrategyInterface
{
	private array $ring = [];
	private int $replicas;
	private bool $sorted = false;

	public function __construct(int $replicas = 3) {
		$this->replicas = $replicas;
	}

	public function addServer(string $group, string $server): void {
		for ($i = 0; $i < $this->replicas; $i++) {
			$hash = $this->hash($server . ':' . $i);
			$this->ring[$hash] = $server;
		}
		$this->sorted = false;
	}

	public function removeServer(string $group, string $server): void {
		for ($i = 0; $i < $this->replicas; $i++) {
			$hash = $this->hash($server . ':' . $i);
			unset($this->ring[$hash]);
		}
		$this->sorted = false;
	}

	public function getServer(string $group, string $key): ?string {
		if (empty($this->ring)) {
			throw new \RuntimeException("No servers registered.");
		}

		if (!$this->sorted) {
			ksort($this->ring);
			$this->sorted = true;
		}

		$hash = $this->hash($key);
		foreach ($this->ring as $ringHash => $server) {
			if ($hash <= $ringHash) {
				return $server;
			}
		}
		return reset($this->ring);
	}

	private function hash(string $key): int {
		return hexdec(substr(md5($key), 0, 8));
	}
}