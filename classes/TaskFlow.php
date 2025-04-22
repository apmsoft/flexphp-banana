<?php
namespace Flex\Banana\Classes;

use Flex\Banana\Classes\Model;

final class TaskFlow extends Model
{
    public const __version = '1.1.0';
    private mixed $active = null;

    private $errorCallback = null;

    public function __construct(?array $args = []) {
        parent::__construct($args);
    }

    public function do(mixed $instance): mixed
    {
        if ($instance instanceof \Closure) {
            try {
                return $instance($this)
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