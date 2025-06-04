<?php
namespace Flex\Banana\Classes\Route;

use Flex\Banana\Classes\Json\JsonDecoder;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Adapters\DbAdapter;
use Flex\Banana\Interfaces\RouteInterface;

class DbRoute extends DbAdapter implements RouteInterface 
{
	private string $table;
	public function __construct(DbManager $db, string $table) {
		# parent
		parent::__construct( $db );
		$this->table = $table;
	}

	#@ RouteInterface
	public function getRoutes(): array 
	{
		$routes = [];
		if($result = $this->db->table($this->table)->select("url,types,flow")->query()){
			while ($row = $result->fetch_assoc()){
				$routes[$row['url']] = [
					'method' => strtoupper($row['types']),
					'tasks' => JsonDecoder::toArray($row['flow'])
				];
			}
		}

		return $routes;
	}
}