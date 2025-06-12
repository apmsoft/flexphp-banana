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

    // 'bcmath' 체크
    if (!extension_loaded('bcmath')) {
        throw new \RuntimeException("The BCMath extension is required for this JumpHashing implementation to work correctly. Please install and enable the 'bcmath' PHP extension.");
    }

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
  }

  private function hash64_for_bcmath(string $key): string {
    $unsigned_crc = sprintf('%u', crc32($key));
    return bcadd($unsigned_crc, '4294967296');
  }
}