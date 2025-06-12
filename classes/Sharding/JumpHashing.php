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

    if (extension_loaded('bcmath')) 
		{
			$hash = $this->hash64_for_bcmath($key);
			$b = -1;
			$j = 0;

			// BCMath 연산을 위한 상수 (문자열)
			$multiplier    = '2862933555777941757';
			$increment     = '1';
			$modulus       = '18446744073709551616'; // 2^64 (uint64 래핑 흉내)
			$shift_divisor = '8589934592';           // 2^33
			$jump_dividend = '2147483648';           // 2^31

			while ($j < $numServers) {
				$b = $j;
				$hash = bcmod(bcadd(bcmul($hash, $multiplier), $increment), $modulus);
				$randomizer = bcadd(bcdiv($hash, $shift_divisor, 0), '1');
				$dividend = bcmul((string)($b + 1), $jump_dividend);
				$j = (int)bcdiv($dividend, $randomizer, 0);
			}

			return $this->servers[$b];
    } else {
			// crc32를 사용해 키를 32비트 정수로 변환합니다.
			$hash = crc32($key);

			// abs()를 사용하여 항상 양수의 인덱스를 얻도록 보장하고, 서버 수로 나눈 나머지를 구합니다.
			$index = abs($hash) % $numServers;

			return $this->servers[$index];
    }
  }

  private function hash64_for_bcmath(string $key): string {
    // crc32는 시스템 환경(32/64비트)에 따라 다른 값을 반환
    $unsigned_crc = sprintf('%u', crc32($key));
    
    // 원본 코드의 | 0x100000000 로직을 bcadd로 구현
    return bcadd($unsigned_crc, '4294967296');
  }
}