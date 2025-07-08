<?php
namespace Flex\Banana\Classes\Text;

class TextUtil
{
	public const __version = '1.2';
	private string $value;
	private array $choseong = [
			'ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ',
			'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'
	];

	public function __construct(string $s)
	{
		// 필수 mbstring 함수가 없는 경우 예외 발생
		if (!extension_loaded('mbstring')) {
        throw new \Exception('The mbstring extension is required for TextUtil to function correctly.');
    }
		$this->value = $s;
	}

	public function append(string|int $s): self
	{
			$this->value .= $s;
			return $this;
	}

	public function prepend(string|int $s): self
	{
			$this->value = $s . $this->value;
			return $this;
	}

	/**
	 * 문자를 지정된 길이부터 특정 문자로 변경
	 * @param int $startNumber 시작 위치 (1부터 시작)
	 * @param int $length      변경할 길이
	 * @param string $chgString 변형될 문자
	 * @return self
	 */
	public function replace(int $startNumber, int $length, string $chgString): self
	{
			$start_index = $startNumber - 1;
			$prefix = mb_substr($this->value, 0, $start_index, 'UTF-8');
			$suffix = mb_substr($this->value, $start_index + $length, null, 'UTF-8');
			$masked = str_repeat($chgString, $length);

			$this->value = $prefix . $masked . $suffix;
			return $this;
	}

	/**
	 * 문자열을 원하는 너비로 자르기
	 * @param int $width 자를 너비 (한글은 2, 영문/숫자는 1로 계산됨)
	 * @param bool $appendEllipsis ... 추가 여부
	 * @param string $strip_tags 제거하지 않을 태그
	 * @return self
	 */
	public function cut(int $width, bool $appendEllipsis = true, string $strip_tags = ''): self
	{
			$str = strip_tags($this->value, $strip_tags);
			$marker = $appendEllipsis ? '...' : '';

			// mb_strimwidth는 지정된 너비보다 길 경우에만 자르고 마커를 붙임
			$this->value = mb_strimwidth($str, 0, $width, $marker, 'UTF-8');
			return $this;
	}
	
	public function numberf(string $str='-') : self
	{
			$result = preg_replace("/[^0-9]*/s", "", $this->value);
			$patterns = [
					10 => '/(\d{3})(\d{3})(\d{4})/',
					11 => '/(\d{3})(\d{4})(\d{4})/',
			];
			$length = strlen($result);
			if(isset($patterns[$length])){
					$this->value = preg_replace($patterns[$length], '\1'.$str.'\2'.$str.'\3', $result);
			}
			return $this;
	}

	/**
	 * 모든 문자의 첫 글자 또는 한글 초성만 추출하기
	 * @return self
	 */
	public function extractFirstChar(): self
	{
			$char = mb_substr($this->value, 0, 1, 'UTF-8');

			if (preg_match('/^\p{Hangul}$/u', $char)) {
					$code = unpack('V', iconv('UTF-8', 'UCS-4LE', $char))[1];
					$unicodeOffset = $code - 0xAC00;
					$choseongIndex = floor($unicodeOffset / 588); // 588 = 21 * 28
					$this->value = $this->choseong[$choseongIndex];
			} else {
					$this->value = $char;
			}
			return $this;
	}

	public function __get(string $propertyName)
	{
			if (property_exists($this, $propertyName)) {
					return $this->{$propertyName};
			}
			return null;
	}
}