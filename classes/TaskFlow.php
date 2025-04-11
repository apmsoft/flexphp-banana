<?php
namespace Flex\Banana\Classes;

use Flex\Banana\Classes\Array\ArrayHelper;

final class TaskFlow extends ArrayHelper
{
    public const __version = '0.9.0';
    private mixed $active = null;
    private ?callable $errorCallback = null;

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

    public function onError(callable $callback): static
    {
        $this->errorCallback = $callback;
        return $this;
    }
}