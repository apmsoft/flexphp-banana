<?php
namespace Flex\Banana\Classes;

use Flex\Banana\Classes\Array\ArrayHelper;

final class TaskFlow extends ArrayHelper
{
    public const __version = '1.0.1';
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

    public function getAll(): array
    {
        return $this->data;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }

    public function onError(callable $callback): static
    {
        $this->errorCallback = $callback;
        return $this;
    }
}