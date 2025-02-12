<?php
namespace Flex\Banana\Classes\Uuid;

class UuidGenerator
{
	public const __version = '1.3.1';
	public function __construct(){
	}

	#@ String
	public function v3(string $uuid, string $keyname) : mixed
	{
		if(!$this->is_valid($uuid)) return false;

        $nhex = str_replace(array('-','{','}'), '', $uuid);
        $nstr = '';

        for($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }

        $hash = md5($nstr . $keyname);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
			substr($hash, 0, 8),
			substr($hash, 8, 4),
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			substr($hash, 20, 12)
		);
	}

	#@ String
	# GenerateUUID V4
	# prekey 에 timestamp, ymdhis, microtime(date('YmdHis') . substr((string)microtime(), 2, 6)) 를 사용할 경우 asc, desc 정렬이 가능함
	public function v4(?string $prekey=null)
	{
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		return ($prekey !== null && $prekey) ? $prekey.'-'.$uuid : $uuid;
	}

	#@ String
	public function v5(string $uuid, string $keyname) : mixed
	{
		if(!$this->is_valid($uuid)) return false;

		$nhex = str_replace(array('-','{','}'), '', $uuid);
		$nstr = '';

		for($i = 0; $i < strlen($nhex); $i+=2) {
			$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		}

		$hash = sha1($nstr . $keyname);

		return sprintf('%08s-%04s-%04x-%04x-%12s',
			substr($hash, 0, 8),
			substr($hash, 8, 4),
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			substr($hash, 20, 12)
		);
	}

	# 시간순 정렬 가능 DESC, ASC
	public function v7(string|int $prekey=null): string
	{
		$timestamp = ($prekey) ? time() + $prekey : floor(microtime(true) * 1000);

		# Random bits (74 bits)
		$randA = random_bytes(5);
		$randB = random_bytes(5);

		# imestamp hex (12 chars)
		$time_hex = str_pad(dechex($timestamp), 12, '0', STR_PAD_LEFT);

		# variant bits
		return sprintf('%s-%s-%s-%s-%s',
			$time_hex,
			bin2hex(substr($randA, 0, 2)),
			'7' . bin2hex(substr($randA, 2, 1)),
			'8' . bin2hex(substr($randA, 3, 1)),
			bin2hex($randB)
		);
	}

	#@ String
	public function is_valid(string $uuid) : mixed {
		return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.'[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
	}
}