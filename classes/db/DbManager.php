<?php
namespace Flex\Banana\Classes\Db;

use \ReflectionClass;
use \Exception;
use \ArrayAccess;

class DbManager implements ArrayAccess
{
    public const __version = '1.0.1';

    private $processor;
    private static $allowedProcessors = [
        DbMySql::class,
        DbPgSql::class,
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

    public function offsetSet($offset, $value): void
    {
        $this->processor->offsetSet($offset, $value);
    }

    public function offsetExists($offset): bool
    {
        return $this->processor->offsetExists($offset);
    }

    public function offsetUnset($offset): void
    {
        $this->processor->offsetUnset($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->processor->offsetGet($offset);
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

    public function __get(string $propertyName)
    {
        return $this->processor->$propertyName;
    }

    public function __set(string $propertyName, mixed $value)
    {
        return $this->processor->$propertyName = $value;
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