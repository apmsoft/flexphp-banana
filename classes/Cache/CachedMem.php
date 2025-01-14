<?php
namespace Flex\Banana\Classes\Cache;

use \Memcached;

class CachedMem
{
    public const __version = '0.1.1';
    private string $cache_key;

    private Memcached $memcached;

    public function __construct(
        private string $host = 'localhost',
        private int $port = 11211
    ) {
        $this->memcached = new Memcached();
        $this->memcached->addServer($this->host, $this->port);

        if (!$this->_serverStatus()) {
            throw new \Exception("Memcached connect fail...");
        }
    }

    public function __invoke(string $cache_key): CachedMem
    {
        $this->cache_key = $cache_key;

        return $this;
    }

    public function __call(string $method, array $params): mixed
    {
        if (!method_exists($this->memcached, $method)) {
            throw new \Exception("Method '{$method}' does not exist in Memcached.");
        }

        try {
            return call_user_func_array([$this->memcached, $method], $params);
        } catch (\Throwable $e) {
            throw new \Exception("Error calling method '{$method}': " . $e->getMessage(), 0, $e);
        }
    }


    # 서버 상태 체크 : 서버가 실패하면 false 반환, 성공하면 true
    public function _serverStatus(): bool
    {
        $stats = $this->memcached->getStats();
        return isset($stats["{$this->host}:{$this->port}"]) && $stats["{$this->host}:{$this->port}"]["pid"] > 0;
    }

    /**
     * 캐시에 키-값 쌍을 설정
     *
     * @param mixed $data 캐싱될 데이터
     * @param int $expiration 캐시 만료 시간 = 0 - 만료되지 않음, 초 단위 >0
     */
    public function _set(mixed $data, int $expiration = 0): CachedMem
    {
        if (!$this->memcached->set($this->cache_key, $data, $expiration)) {
            throw new \Exception("Memcached set failed for key: {$this->cache_key}");
        }
        return $this;
    }

    # 캐시에서 값을 가져오기
    public function _get(): mixed
    {
        $data = $this->memcached->get($this->cache_key);
        if ($data === false && $this->memcached->getResultCode() !== Memcached::RES_SUCCESS) {
            $data = null; // 캐시 값이 없는 경우
        }

        return $data;
    }

    # 캐시에서 키를 삭제
    public function _delete(): void
    {
        if (!$this->memcached->delete($this->cache_key)) {
            throw new \Exception("Memcached delete failed for key: {$this->cache_key}");
        }
    }

    # 캐시 비우기
    public function _clear(): void
    {
        if (!$this->memcached->flush()) {
            throw new \Exception('Memcached clear failed');
        }
    }

    # 캐시 접속 종료
    public function _close(): void
    {
        $this->memcached->quit();
    }

    # 자동소멸
    public function __destruct()
    {
        $this->_close();
    }
}
