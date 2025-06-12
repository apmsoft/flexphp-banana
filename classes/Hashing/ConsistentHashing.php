<?php
namespace Flex\Banana\Classes\Hashing;

final class ConsistentHashing {
    private static array $ring = [];
    private static int $replicas = 3;
    private static bool $sorted = false;

    public static function setReplicas(int $replicas): void {
        self::$replicas = $replicas;
    }

    public static function addServer(string $server): void {
        for ($i = 0; $i < self::$replicas; $i++) {
            $hash = self::hash($server . ':' . $i);
            self::$ring[$hash] = $server;
        }
        self::$sorted = false; // 정렬 미루기
    }

    public static function removeServer(string $server): void {
        for ($i = 0; $i < self::$replicas; $i++) {
            $hash = self::hash($server . ':' . $i);
            unset(self::$ring[$hash]);
        }
        self::$sorted = false;
    }

    public static function getServer(string $key): ?string {
        if (!self::$sorted) {
            ksort(self::$ring);
            self::$sorted = true;
        }

        $hash = self::hash($key);
        foreach (self::$ring as $ringHash => $server) {
            if ($hash <= $ringHash) {
                return $server;
            }
        }
        return reset(self::$ring);
    }

    private static function hash(string $key): int {
        return hexdec(substr(md5($key), 0, 8));
    }
}