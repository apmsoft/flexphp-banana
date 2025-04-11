<?php
namespace Flex\Banana\Classes;

use Flex\Banana\Classes\Array\ArrayHelper;

final class TaskFlow extends ArrayHelper
{
    public const __version = '1.0.0';
    private mixed $active = null;
    private ?callable $errorCallback = null;
    private array $data = [];

    public function __construct() {}

    public function do(mixed $instance): mixed
    {
        if ($instance instanceof \Closure) {
            try {
                return $instance($this);
            } catch (\Throwable $e) {
                Log::e($e->getMessage() . "\n" . $e->getTraceAsString());
                if (is_callable($this->errorCallback)) {
                    call_user_func($this->errorCallback, $e);
                }
            }
            return $this;
        }

        $this->active = $instance;
        return $instance;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): static
    {
        unset($this->data[$key]);
        return $this;
    }

    public function getAll(): array
    {
        return $this->data;
    }

    public function onError(callable $callback): static
    {
        $this->errorCallback = $callback;
        return $this;
    }
}