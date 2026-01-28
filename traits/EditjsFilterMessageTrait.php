<?php
namespace Flex\Banana\Traits;

use Flex\Banana\Classes\Html\XssChars;
use Flex\Banana\Classes\Text\TextUtil;

# javascript editjs 텍스트 내용만 찾아 특수문자 제거한 한줄 문장으로 만들기
trait EditjsFilterMessageTrait
{
	const TEXT_LIKE_TYPES = ["paragraph", "header", "quote", "image", "list", "code", "table"];
	const MEDIA_LIKE_TYPES = ['embed', 'linkTool', 'attaches'];
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
		$outHtml = "";
		if(is_array($descriptions) && isset($descriptions['blocks'])){
			foreach($descriptions['blocks'] as $idx => $content)
			{
				$tempHTML = "";

				# 미디어 타입
				if(in_array($content['type'], self::MEDIA_LIKE_TYPES)){
					$tempHTML = strtoupper(self::MEDIA_TYPES_TEXT[$content['type']] ?? "");
				}

				# 텍스트 타입
				else if(in_array($content['type'], self::TEXT_LIKE_TYPES))
				{
					if($content['type'] === "image") {
						$tempHTML = $content['data']['caption'] ?? "Image";
					}else if($content['type'] === "list") {
						if (!empty($content['data']['items']) && is_array($content['data']['items'])) {
							$tempHTML = implode(", ", $content['data']['items']);
						}
					}else if($content['type'] === "code") {
						$tempHTML = $content['data']['code'] ?? "";
					}else {
						$tempHTML = $content['data']['text'] ?? "";
					}
				}else{
					continue;
				}

				if(trim($tempHTML)){
					$xssChars = new XssChars( $tempHTML );
					foreach($allowTags as $tag) {
						$xssChars->setAllowTags($tag);
					}
					$outHtml .= $xssChars->getXss()." ";
				}
			}
		}

		return $outHtml;
	}

	public function getHtml(array $descriptions, array $allowTags = []) : string
	{
		$html = "";

		// getHtml에서 기본으로 필요한 태그들은 자동 허용(원하면 allowTags로 추가/확장 가능)
		$baseAllowTags = [
			"<p>","<br>",
			"<b>","<strong>","<i>","<em>","<u>","<s>",
			"<h1>","<h2>","<h3>","<h4>","<h5>","<h6>",
			"<blockquote>",
			"<ul>","<ol>","<li>",
			"<pre>","<code>",
			"<a>",
			"<figure>","<img>","<figcaption>",
			"<table>","<thead>","<tbody>","<tr>","<th>","<td>",
			"<div>","<span>"
		];
		$allowTags = array_values(array_unique(array_merge($baseAllowTags, $allowTags)));

		if(is_array($descriptions) && isset($descriptions['blocks']) && is_array($descriptions['blocks'])) 
		{
			foreach($descriptions['blocks'] as $content) 
			{
				$type = $content['type'] ?? '';
				$data = $content['data'] ?? [];

				// 미디어 타입은 간단한 표시(원하면 아래에서 더 구체 렌더 가능)
				if (in_array($type, self::MEDIA_LIKE_TYPES, true)) {
					$label = strtoupper(self::MEDIA_TYPES_TEXT[$type] ?? "MEDIA");
					$html .= "<p>{$label}</p>";
					continue;
				}

				// 텍스트/콘텐츠 타입 렌더링
				if (!in_array($type, self::TEXT_LIKE_TYPES, true)) {
					continue;
				}

				if ($type === "paragraph") {
					$text = (string)($data['text'] ?? '');
					if (trim($text) !== '') $html .= "<p>{$text}</p>";

				} else if ($type === "header") {
					$level = (int)($data['level'] ?? 2);
					if ($level < 1 || $level > 6) $level = 2;
					$text = (string)($data['text'] ?? '');
					if (trim($text) !== '') $html .= "<h{$level}>{$text}</h{$level}>";

				} else if ($type === "quote") {
					$text = (string)($data['text'] ?? '');
					$caption = (string)($data['caption'] ?? '');
					if (trim($text) !== '') {
						$capHtml = trim($caption) !== '' ? "<br><span>{$caption}</span>" : "";
						$html .= "<blockquote>{$text}{$capHtml}</blockquote>";
					}

				} else if ($type === "list") {
					$style = (string)($data['style'] ?? 'unordered'); // 'ordered' | 'unordered'
					$items = $data['items'] ?? [];
					if (is_array($items) && !empty($items)) {
						$tag = ($style === 'ordered') ? 'ol' : 'ul';
						$html .= "<{$tag}>";
						foreach($items as $it) {
							// Editor.js list item이 문자열/HTML일 수 있음
							$val = is_string($it) ? $it : (string)($it['content'] ?? '');
							if (trim($val) !== '') $html .= "<li>{$val}</li>";
						}
						$html .= "</{$tag}>";
					}

				} else if ($type === "code") {
					$code = (string)($data['code'] ?? '');
					if (trim($code) !== '') {
						// code는 그대로 넣되 XssChars에서 필터링 + <pre><code> 허용
						$html .= "<pre><code>{$code}</code></pre>";
					}

				} else if ($type === "table") {
					// Editor.js table tool: data.content = [[...],[...]]
					$rows = $data['content'] ?? [];
					if (is_array($rows) && !empty($rows)) {
						$html .= "<table><tbody>";
						foreach ($rows as $row) {
							if (!is_array($row)) continue;
							$html .= "<tr>";
							foreach ($row as $cell) {
								$cellText = is_string($cell) ? $cell : "";
								$html .= "<td>{$cellText}</td>";
							}
							$html .= "</tr>";
						}
						$html .= "</tbody></table>";
					}

				} else if ($type === "image") {
					$url = (string)(
						$data['file']['url']
						?? $data['url']
						?? ''
					);
					$caption = (string)($data['caption'] ?? '');
					$withBorder = !empty($data['withBorder']);
					$withBackground = !empty($data['withBackground']);
					$stretched = !empty($data['stretched']);

					if (trim($url) !== '') {
						$classes = [];
						if ($withBorder) $classes[] = "editjs-img-border";
						if ($withBackground) $classes[] = "editjs-img-bg";
						if ($stretched) $classes[] = "editjs-img-stretched";
						$classAttr = !empty($classes) ? ' class="'.implode(' ', $classes).'"' : '';

						#$capHtml = trim($caption) !== '' ? "<figcaption>{$caption}</figcaption>" : "";
						$html .= "<figure{$classAttr}><img src=\"{$url}\" alt=\"\"></figure>";
					} else {
						// URL이 없으면 최소한 캡션이라도
						$fallback = trim($caption) !== '' ? $caption : "Image";
						$html .= "<p>{$fallback}</p>";
					}
				}
			}
		}

		// 마지막으로 XSS 클리닝(허용 태그만 살림)
		if (trim($html) !== '') {
			$xssChars = new XssChars($html);
			foreach($allowTags as $tag) $xssChars->setAllowTags($tag);
			$html = $xssChars->cleanTags();
		}

		return $html;
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