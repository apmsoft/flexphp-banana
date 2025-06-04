<?php
namespace Flex\Banana\Classes\Route;

use Flex\Banana\Classes\Json\JsonDecoder;
use Flex\Banana\Classes\Log;
use Flex\Banana\Interfaces\RouteInterface;

class JsonRoute implements RouteInterface 
{
  public function __construct(private string $baseDir) {}

  #@ RouteInterface
  public function getRoutes(): array 
  {
    $indexPath = "{$this->baseDir}/res/routes/index.json";
    if (!file_exists($indexPath)) return [];
    $taskFiles = JsonDecoder::toArray(file_get_contents($indexPath));
    $routes = [];
    foreach ($taskFiles as $file) {
        $path = "{$this->baseDir}/res/routes/{$file}.json";
        $routes = array_merge($routes, JsonDecoder::toArray(file_get_contents($path)));
    }
    return $routes;
  }
}