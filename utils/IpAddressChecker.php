<?php
namespace Flex\Banana\Utils;

use Flex\Banana\Classes\Log;

class IpAddressChecker
{
	public const __version = "0.1.0";

	public function __construct() {}

	public function execute(string $clientIp, array|string $allowed_ips): bool
	{
		# 전체허용인지 체크
		if($allowed_ips === "0.0.0.0"){
			return true;
		}

		# 허락된 ip 주소목록 추출
		$allowedIps = [];
		if(is_string($allowed_ips)){
			$allowedIps = array_map('trim', explode(",", $allowed_ips));
		}else {
			$allowedIps = $allowed_ips;
		}

		// IP 확인
		// $clientIp = $this->requested->getAttribute('CLIENT_IP') ?? "x.x.x.x"; 
		Log::d($clientIp, $allowedIps);
    if (in_array($clientIp, $allowedIps)) {
      return true;
    }

    // Ip 패턴 192.168.*.*
    foreach ($allowedIps as $allowedIp) {
			// '*'를 정규식의 '.*'로 변환하여 패턴 매칭
			if (strpos($allowedIp, '*') !== false) {
				$pattern = '/^' . str_replace('\*', '.*', preg_quote($allowedIp, '/')) . '$/';
				if (preg_match($pattern, $clientIp)) {
					return true;
				}
			}
    }

    return false;
	}
}
