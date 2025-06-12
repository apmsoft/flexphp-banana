<?php
namespace Flex\Banana\Classes\Hashing;

final class ConsistentHashing {
    private static array $ring = [];
    private static int $replicas = 3;
    private static bool $initialized = false;

    public static function init(array $servers, int $replicas = 3): void {
        self::$replicas = $replicas;
        self::$ring = [];
        foreach ($servers as $server) {
            for ($i = 0; $i < $replicas; $i++) {
                $hash = self::hash($server . ':' . $i);
                self::$ring[$hash] = $server;
            }
        }
        ksort(self::$ring);
        self::$initialized = true;
    }

    private static function hash(string $key): int {
        return hexdec(substr(md5($key), 0, 8));
    }

    public static function getServer(string $key): ?string {
        if (!self::$initialized) {
            throw new \RuntimeException("ConsistentHashing not initialized.");
        }
        $hash = self::hash($key);
        foreach (self::$ring as $ringHash => $server) {
            if ($hash <= $ringHash) {
                return $server;
            }
        }
        return reset(self::$ring);
    }
}