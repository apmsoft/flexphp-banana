<?php
namespace Flex\Banana\Classes\Uuid;

final class UuidGenerator
{
	public const __version = '1.4.0';

	public function __construct() {}

	# UUID v4 (random)
	public function v4(): string
	{
		$data = random_bytes(16);

		// Version 4, Variant RFC 4122
		$data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
		$data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

		return $this->fromBinary($data);
	}

	/**
	 * UUID v5 (name-based, SHA-1)
	 */
	public function v5(string $namespaceUuid, string $name): string|false
	{
		$nsBytes = $this->toBinary($namespaceUuid);
		if ($nsBytes === false) return false;

		$hash = sha1($nsBytes . $name, true);
		$bytes = substr($hash, 0, 16);

		// Version 5, Variant RFC 4122
		$bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x50);
		$bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

		return $this->fromBinary($bytes);
	}

	# v7 
	public function v7(): string
	{
		return $this->fromBinary($this->v7_bytes());
	}

	# UUID v7 - Raw Binary (16 bytes) 반환
	public function v7_bytes(): string
	{
		// 48-bit Timestamp (Big-endian)
		$ts = (int) (microtime(true) * 1000);
		
		// pack 'J'는 64비트 정수(8바이트). 앞의 2바이트를 잘라내면 48비트(6바이트)가 됨.
		$bin = substr(pack('J', $ts), 2); 

		// 10-bit Random Data
		$random = random_bytes(10);

		// Merge & Apply Version/Variant
		// Version 7
		$random[0] = chr((ord($random[0]) & 0x0f) | 0x70);
		// Variant RFC 4122
		$random[2] = chr((ord($random[2]) & 0x3f) | 0x80);

		return $bin . $random;
	}

	# 유효성 검사 : 하이픈이 정확히 위치한 36자 or 하이픈 없는 32자 모두 허용
	public function is_valid(string $uuid): bool
	{
		return preg_match('/^(\{?[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}\}?)$/i', $uuid) === 1;
	}

	public function is_valid_v4(string $uuid): bool
	{
		if (!$this->is_valid($uuid)) return false;
		$u = $this->normalize($uuid);
		return $u[12] === '4' && in_array($u[16], ['8','9','a','b'], true);
	}

	public function is_valid_v7(string $uuid): bool
	{
		if (!$this->is_valid($uuid)) return false;
		$u = $this->normalize($uuid);
		return $u[12] === '7' && in_array($u[16], ['8','9','a','b'], true);
	}

	# UUID -> Binary(16)
	# _id BINARY(16) NOT NULL PRIMARY KEY,
	public function toBinary(string $uuid): string|false
	{
		if (!$this->is_valid($uuid)) return false;
		return hex2bin($this->normalize($uuid));
	}

	# Binary(16) -> UUID
	public function fromBinary(string $bytes): string
	{
		if (strlen($bytes) !== 16) {
			throw new \InvalidArgumentException('UUID bytes must be exactly 16 bytes.');
		}

		$hex = bin2hex($bytes);

		return sprintf('%s-%s-%s-%s-%s',
			substr($hex, 0, 8),
			substr($hex, 8, 4),
			substr($hex, 12, 4),
			substr($hex, 16, 4),
			substr($hex, 20, 12)
		);
	}

	# 내부 정규화 (소문자 + 하이픈 제거)
	private function normalize(string $uuid): string
	{
		return strtolower(str_replace(['{', '}', '-'], '', $uuid));
	}
}