<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\WhereInterface;
use \ReflectionClass;
use \Exception;

class WhereHelper
{
    public const __version = '1.0.1';

    private $processor;
    private static $allowedProcessors = [
        WhereSql::class,
        WhereCouch::class,
    ];

    public function __construct($processor)
    {
        $this->setProcessor($processor);
    }

    private function setProcessor($processor): void
    {
        $reflection = new ReflectionClass($processor);
        if (!in_array($reflection->getName(), self::$allowedProcessors)) {
            throw new Exception("Unsupported processor type: " . $reflection->getName());
        }

        $this->processor = $processor;
    }

	public function case(string $field_name, string $condition, mixed $value, bool $is_qutawrap = true, bool $join_detection = true): self{
		$this->processor->case($field_name, $condition, $value, $is_qutawrap, $join_detection);
		return $this;
	}
    public function begin(string $coord): self{
		$this->processor->begin($coord);
		return $this;
	}
    public function end(): self{
		$this->processor->end();
		return $this;
	}
    public function fetch(): array{
		return $this->processor->fetch();
	}

    public function __call($name, $arguments)
    {
        $reflection = new ReflectionClass($this->processor);
        if ($reflection->hasMethod($name)) {
            $method = $reflection->getMethod($name);
            if ($method->isPublic()) {
                return $method->invokeArgs($this->processor, $arguments);
            }
        }

        // 프로세서의 __call 메소드 호출
        if ($reflection->hasMethod('__call')) {
            return $this->processor->__call($name, $arguments);
        }

        throw new Exception("Method $name does not exist in " . get_class($this->processor));
    }

    public function __get($propertyName) : mixed
    {
        return $this->processor->__get($propertyName);
    }

    public function __destruct()
    {
        $this->processor->__destruct();
    }

    public static function addProcessor(string $processorClass): void
    {
        if (!class_exists($processorClass)) {
            throw new Exception("Class $processorClass does not exist");
        }

        if (!in_array($processorClass, self::$allowedProcessors)) {
            self::$allowedProcessors[] = $processorClass;
        }
    }

    public static function getAllowedProcessors(): array
    {
        return self::$allowedProcessors;
    }
}