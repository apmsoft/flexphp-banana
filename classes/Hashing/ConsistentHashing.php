<?php
namespace Flex\Banana\Classes\Hashing;

final class ConsistentHashing {
  private static array $rings = [];       // 그룹별 해시 링
  private static array $replicas = [];    // 그룹별 replica 수
  private static array $sorted = [];      // 그룹별 정렬 여부

  public static function setReplicas(string $group, int $replicas): void {
    self::$replicas[$group] = $replicas;
  }

  public static function addServer(string $group, string $server): void {
    $replicas = self::$replicas[$group] ?? 3; // 기본값 3
    for ($i = 0; $i < $replicas; $i++) {
      $hash = self::hash($server . ':' . $i);
      self::$rings[$group][$hash] = $server;
    }
    self::$sorted[$group] = false;
  }

  public static function removeServer(string $group, string $server): void {
    $replicas = self::$replicas[$group] ?? 3;
    for ($i = 0; $i < $replicas; $i++) {
        $hash = self::hash($server . ':' . $i);
        unset(self::$rings[$group][$hash]);
    }
    self::$sorted[$group] = false;
  }

  public static function getServer(string $group, string $key): ?string {
    if (!isset(self::$rings[$group]) || empty(self::$rings[$group])) {
      throw new \RuntimeException("No servers registered for group: {$group}");
    }

    if (!self::$sorted[$group]) {
      ksort(self::$rings[$group]);
      self::$sorted[$group] = true;
    }

    $hash = self::hash($key);
    foreach (self::$rings[$group] as $ringHash => $server) {
      if ($hash <= $ringHash) {
        return $server;
      }
    }
    return reset(self::$rings[$group]);
  }

  private static function hash(string $key): int {
    return hexdec(substr(md5($key), 0, 8));
  }
}