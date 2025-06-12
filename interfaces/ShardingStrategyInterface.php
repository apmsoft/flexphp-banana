<?php
namespace Flex\Banana\Interfaces;

interface ShardingStrategyInterface {
  public function addServer(string $group, string $server, int $weight = 1): void;
  public function removeServer(string $group, string $server): void;
  public function getServer(string $group, string $key): ?string;
}