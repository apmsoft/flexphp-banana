<?php
namespace Flex\Banana\Classes;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Json\JsonDecoder;
use Flex\Banana\Classes\Log;
use Flex\Banana\Adapters\DbAdapter;

final class RouteLoader extends DbAdapter
{
	private string $baseDir;
	private array $jsonRoutes;
	private array $dbRoutes;

	public function __construct(string $baseDir, DbManager $db)
	{
		$this->baseDir    = rtrim($baseDir, '/');
		$this->jsonRoutes = [];
		$this->dbRoutes   = [];

		# parent
    parent::__construct( $db );
	}

	/**
	 * 최종 라우트 목록 반환
	 */
	public function load(): array
	{
		$routes = array_merge(
			$this->jsonRoutes,
			$this->dbRoutes
		);
		return $this->expandVersionedRoutes($routes);
	}

	/**
	 * JSON 라우트 파일 불러오기
	 */
	public function jsonRoutes(): self
	{
		$indexPath = "{$this->baseDir}/res/routes/index.json";
		if (!file_exists($indexPath)) {
			Log::e("RouteLoader: index.json not found at {$indexPath}");
			return $this;
		}

		$taskFiles = JsonDecoder::toArray(file_get_contents($indexPath));
		foreach ($taskFiles as $file) {
			$path = "{$this->baseDir}/res/routes/{$file}.json";
			$data = JsonDecoder::toArray(file_get_contents($path));
			$this->jsonRoutes = array_merge($this->jsonRoutes, $data);
		}

		return $this;
	}

	/**
	 * DB 기반 라우트 불러오기
	 */
	public function dbRoutes(string $table): self
	{
		if($result = $this->db->table($table)->select("url,types,flow")->query()){
			while ($row = $result->fetch_assoc()){
				$this->dbRoutes[$row['url']] = [
					'method' => strtoupper($row['types']),
					'tasks' => JsonDecoder::toArray($row['flow'])
				];
			}
		}

		return $this;
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