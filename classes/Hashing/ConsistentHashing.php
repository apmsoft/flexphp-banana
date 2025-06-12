<?php
namespace Flex\Banana\Classes\Hashing;

use Flex\Banana\Classes\Log;

# 서버 일괄성 있게 분산 해싱
final class ConsistentHashing {
  private $ring = [];
  private $replicas;

  public function __construct($replicas = 3) {
    $this->replicas = $replicas;
  }

  private function hash($key) {
    return hexdec(substr(md5($key), 0, 8)); // 32비트 해시
  }

  public function addServer($server) {
    for ($i = 0; $i < $this->replicas; $i++) {
      $hash = $this->hash($server . ':' . $i);
      $this->ring[$hash] = $server;
    }
    ksort($this->ring); // 정렬하여 해시 링 생성
  }

  public function removeServer($server) {
    for ($i = 0; $i < $this->replicas; $i++) {
      $hash = $this->hash($server . ':' . $i);
      unset($this->ring[$hash]);
    }
  }

  public function getServer($key) {
    $hash = $this->hash($key);
    foreach ($this->ring as $ringHash => $server) {
      if ($hash <= $ringHash) {
        return $server;
      }
    }
    return reset($this->ring); // 링을 순환하여 첫 번째 서버 반환
  }
}