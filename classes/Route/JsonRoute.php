<?php
namespace Flex\Banana\Classes\Route;

use Flex\Banana\Classes\Json\JsonDecoder;
use Flex\Banana\Classes\Log;
use Flex\Banana\Interfaces\RouteInterface;

class JsonRoute implements RouteInterface 
{
  public function __construct(
    private string $dir,
    private string $filename
  ) {}

  #@ RouteInterface
  # {$this->dir}/res/routes/index.json
  public function getRoutes(): array 
  {
    $indexPath = sprintf("%s/%s", $this->dir, $this->filename);
    if (!file_exists($indexPath)) return [];
    $taskFiles = JsonDecoder::toArray(file_get_contents($indexPath));
    $routes = [];
    foreach ($taskFiles as $file) {
        $path = sprintf("%s/%s.json", $this->dir, $file);
        $routes = array_merge($routes, JsonDecoder::toArray(file_get_contents($path)));
    }
    return $routes;
  }
}