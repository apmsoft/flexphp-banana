<?php
namespace Flex\Banana\Classes\Sharding;

use Flex\Banana\Interfaces\ShardingStrategyInterface;

final class ConsistentHashing implements ShardingStrategyInterface
{
	private array $ring = [];                  // 해시 링
	private array $weights = [];               // 서버별 가중치 기록
	private int $replicaUnit;                  // 기본 replica 단위
	private bool $sorted = false;

	public function __construct(int $replicaUnit = 3) {
		$this->replicaUnit = $replicaUnit;
	}

	public function addServer(string $group, string $server, int $weight = 1): void {
		$this->weights[$server] = $weight;
		$replicas = $this->replicaUnit * $weight;

		for ($i = 0; $i < $replicas; $i++) {
			$hash = $this->hash($server . ':' . $i);
			$this->ring[$hash] = $server;
		}
		$this->sorted = false;
	}

	public function removeServer(string $group, string $server): void {
		$weight = $this->weights[$server] ?? 1;
		$replicas = $this->replicaUnit * $weight;

		for ($i = 0; $i < $replicas; $i++) {
			$hash = $this->hash($server . ':' . $i);
			unset($this->ring[$hash]);
		}

		unset($this->weights[$server]);
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
		return reset($this->ring); // 링 끝을 넘으면 처음부터
	}

	private function hash(string $key): int {
		return hexdec(substr(md5($key), 0, 8)); // 32비트 해시
	}
}