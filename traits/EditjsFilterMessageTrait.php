<?php
namespace Flex\Banana\Traits;

use Flex\Banana\Classes\Html\XssChars;
use Flex\Banana\Classes\Text\TextUtil;

# javascript editjs 텍스트 내용만 찾아 특수문자 제거한 한줄 문장으로 만들기
trait EditjsFilterMessageTrait
{
	const TEXT_LIKE_TYPES = ["paragraph", "header", "quote", "image", "list", "code"];
	const MEDIA_LIKE_TYPES = ['embed', 'linkTool', 'attaches', 'table'];
	const MEDIA_TYPES_TEXT = ['embed' => "미디어", 'linkTool' => "웹 링크", 'attaches'=>"파일 첨부", 'table' => "표"];

	/**
	 * Undocumented function
	 *
	 * @param array $descriptions
	 * @param integer $length
	 * @param array $allowTags
	 * @return string
	 */
	public function getText(array $descriptions, array $allowTags=[]) : string 
	{
		$text = "";
		if(is_array($descriptions) && isset($descriptions['blocks'])){
			foreach($descriptions['blocks'] as $idx => $content)
			{
				$tempText = "";

				# 미디어 타입
				if(in_array($content['type'], self::MEDIA_LIKE_TYPES)){
					$tempText = strtoupper(self::MEDIA_TYPES_TEXT[$content['type']] ?? "");
				}

				# 텍스트 타입
				else if(in_array($content['type'], self::TEXT_LIKE_TYPES))
				{
					if($content['type'] === "image") {
						$tempText = $content['data']['caption'] ?? "Image";
					}else if($content['type'] === "list") {
						if (!empty($content['data']['items']) && is_array($content['data']['items'])) {
							$tempText = implode(", ", $content['data']['items']);
						}
					}else if($content['type'] === "code") {
						$tempText = $content['data']['code'] ?? "";
					}else {
						$tempText = $content['data']['text'] ?? "";
					}
				}else{
					continue;
				}

				if(trim($tempText)){
					$xssChars = new XssChars( $tempText );
					foreach($allowTags as $tag) {
						$xssChars->setAllowTags($tag);
					}
					$text .= $xssChars->cleanTags()." ";
				}
			}
		}

		return $text;
	}

	public function getTextCut(array $descriptions, int $length, array $allowTags=["<b>","<strong>"]) : string
	{
		$text = "";
		$text = $this->getText($descriptions, $allowTags);
		
		# 문자 자르기
		if($text && $length > 0) {
			$text = (new TextUtil( $text ))->cut($length)->value;
		}
		return $text;
	}
}