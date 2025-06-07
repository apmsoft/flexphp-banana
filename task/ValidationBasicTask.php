<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\Log;

class ValidationBasicTask
{
	public const __version = '0.2.1';

	public function __construct(
		private array $enums
	){
			// Log::d("** ValidationBasicTask enums", $this->enums);
	}

	public function execute(array $requested) : void
	{
		try {
			foreach ($this->enums as $item) {
				if (!is_array($item) || count($item) === 0) {
					continue;
				}

				$enum = $item[0];
				$options = array_slice($item, 1);

				// 필요한 경우 클래스 문자열을 ENUM 인스턴스로 변환
				if (is_string($enum) && enum_exists($enum)) {
					$enum = $enum::cases()[0];
				}

				if (!($enum instanceof \BackedEnum)) {
					continue;
				}

				$key = $enum->value;
				$enum->validate($requested[$key] ?? '', ...$options);
			}
		} catch (\Exception $e) {
				throw new \Exception($e->getMessage());
		}
	}
}