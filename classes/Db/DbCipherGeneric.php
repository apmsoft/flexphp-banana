<?php
namespace Flex\Banana\Classes\Db;

use \ReflectionClass;
use \Exception;

class DbCipherGeneric
{
    public const __version = '1.0';

    private $processor;
    private static $allowedProcessors = [
        CipherMysqlAes256Cbc::class,
        CipherPgsqlAes256Cbc::class,
        CipherPgsqlBasic::class,
        CipherPgsqlAes::class,
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

    public function __call($name, $arguments)
    {
        $reflection = new ReflectionClass($this->processor);
        if (!$reflection->hasMethod($name)) {
            throw new Exception("Method $name does not exist in " . get_class($this->processor));
        }

        $method = $reflection->getMethod($name);
        if (!$method->isPublic()) {
            throw new Exception("Method $name is not public in " . get_class($this->processor));
        }

        return $method->invokeArgs($this->processor, $arguments);
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