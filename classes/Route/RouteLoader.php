<?php
namespace Flex\Banana\Classes\Route;

use Flex\Banana\Classes\Log;
use Flex\Banana\Interfaces\RouteInterface;

final class RouteLoader
{
	private array $sources = [];

  public function addSource(RouteInterface $source): self {
    $this->sources[] = $source;
    return $this;
  }

	public function routes(): array {
    $merged = [];
    foreach ($this->sources as $source) {
      $merged = array_merge($merged, $source->getRoutes());
    }
    return $this->expandVersionedRoutes($merged);
  }

	/**
	 * [/t1,v1]/path 형식 확장
	 */
	private function expandVersionedRoutes(array $rawRoutes): array
	{
		$expanded = [];

		foreach ($rawRoutes as $routePattern => $config) 
		{
			if (preg_match('#^\[(.*?)\](/.+)$#', $routePattern, $matches)) 
			{
				$prefixes = explode(',', $matches[1]);
				$path     = $matches[2];

				foreach ($prefixes as $prefix) 
				{
					$prefix   = rtrim($prefix, '/');
					$fullPath = $prefix . $path;
					$expanded[$fullPath] = $config;
				}
			} else {
				$normalizedPath = '/' . ltrim($routePattern, '/');
				$expanded[$normalizedPath] = $config;
			}
		}

		return $expanded;
	}
}