<?php
namespace Flex\Banana\Classes;

use Flex\Banana\Classes\Model;

final class TaskFlow extends Model
{
    public const __version = '1.2.0';
    private mixed $active = null;
    private array $adapters = [];

    private $errorCallback = null;

    public function __construct(?array $args = []) {
        parent::__construct($args);
    }

    public function do(mixed $instance): mixed
    {
        if ($instance instanceof \Closure) {
            try {
                $instance($this);
                return $this;
            } catch (\Throwable $e) {
                Log::e($e->getMessage() . "\n" . $e->getTraceAsString());
                if (is_callable($this->errorCallback)) {
                    call_user_func($this->errorCallback, $e);
                }
            }
        }

        $this->active = $instance;
        return $this;  // 변경: 클로저가 아닌 경우 $instance($this) 제거
    }

    public function adapter(string $name): static
    {
        $adapter = $this->adapters[$name] ?? null;

        if (is_object($adapter) && method_exists($adapter, 'process')) {
            $adapter->process($this);
        }

        return $this;
    }

    public function registerAdapter(object $adapter): static
    {
        if (is_object($adapter)) {
            $fullClass = get_class($adapter);
            $className = substr(strrchr($fullClass, '\\'), 1);
            $this->adapters[$className] = $adapter;
        } else {
            throw new \InvalidArgumentException("Invalid arguments for registerAdapter()");
        }

        return $this;
    }

    public function onError(callable $callback): static
    {
        $this->errorCallback = $callback;
        return $this;
    }

    public function __destruct() {
        parent::__destruct();
        unset($this->active);
        unset($this->errorCallback);
    }
}