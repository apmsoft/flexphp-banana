<?php
namespace Flex\Banana\Classes\Html;

# purpose : xss 방지 및 AI Markdown 파싱 지원
class XssChars
{
  public const __version = '2.0.0';
  private string $description;
  private array $allow_tags = [];

  public function __construct(string $description){
    $this->description = $description;
  }

  # 허용 태그 설정
  public function setAllowTags(array|string $value) : void{
    if(is_array($value)) $this->allow_tags = array_merge($this->allow_tags,$value);
    else $this->allow_tags[] = $value;
  }

  # strip_tags
  public function cleanTags() : string{
    return strip_tags(htmlspecialchars_decode($this->description),implode('', $this->allow_tags));
  }

  # Xss 태그 처리 (기존 로직 유지)
  public function cleanXssTags() : string
  {
    $xss_tags = array(
      '@<script[^>]*?>.*?</script>@si',
      '@<style[^>]*?>.*?</style>@siU',
      '@<iframe[^>]*?>.*?</iframe>@si',
      '@<meta[^>]*?>.*?>@si',
      '@<form[^>]*?>.*?>@si',
      '@]script[^>]*?>.*?]/script>@si', 
      '/:*?expression\(.*?\)/si',
      '/:*?binding:(.*?)url\(.*?\)/si',
      '/javascript:[^\"\']*/si',
      '/vbscript:[^\"\']*/si',
      '/livescript:[^\"\']*/si',
      '@<![\s\S]*?--[ \t\n\r]*>@'
    );

		$event_tags = array(
			'dynsrc','datasrc','frameset','ilayer','layer','applet',
			'onabort','onactivate','onafterprint','onsubmit','onunload',
			'onafterupdate','onbeforeactivate','onbeforecopy','onbeforecut',
			'onbeforedeactivate','onbeforeeditfocus','onbeforepaste','onbeforeprint',
			'onbeforeunload','onbeforeupdate','onblur','onbounce','oncellchange',
			'onchange','onclick','oncontextmenu','oncontrolselect','oncopy','oncut',
			'ondataavaible','ondatasetchanged','ondatasetcomplete','ondblclick',
			'ondeactivate','ondrag','ondragdrop','ondragend','ondragenter',
			'ondragleave','ondragover','ondragstart','ondrop','onerror','onerrorupdate',
			'onfilterupdate','onfinish','onfocus','onfocusin','onfocusout','onhelp',
			'onkeydown','onkeypress','onkeyup','onlayoutcomplete','onload','onlosecapture',
			'onmousedown','onmouseenter','onmouseleave','onmousemove','onmoveout',
			'onmouseover','onmouseup','onmousewheel','onmove','onmoveend','onmovestart',
			'onpaste','onpropertychange','onreadystatechange','onreset','onresize',
			'onresizeend','onresizestart','onrowexit','onrowsdelete','onrowsinserted',
			'onscroll','onselect','onselectionchange','onselectstart','onstart','onstop',
			'onpointerdown','onpointerup','onpointermove','onpointerover','onpointerout',
			'onpointerenter','onpointerleave','onpointercancel','ongotpointercapture','onlostpointercapture',
			'onwheel','onanimationstart','onanimationend','onanimationiteration','ontransitionend'
		);

    if(is_array($this->allow_tags)){
      $this->allow_tags = explode(',',strtr(implode(',',$this->allow_tags),['<'=>'','>'=>'']));
      $tmp_eventag= str_replace($this->allow_tags,'',implode('|',$event_tags));
      $event_tags = explode('|',$tmp_eventag);
    }

    return preg_replace($xss_tags, '', str_ireplace($event_tags,'_badtags',$this->description));
  }

  # 자동 링크 걸기
  public function setAutoLink() : string
  {
    // 허용된 프로토콜만 화이트리스트로 제한
		$allowed_protocols = ['http', 'https', 'mailto'];
		$homepage_pattern = "/([^\"\'\=])(mms|market|http|https|HTTP|ftp|FTP|telnet|TELNET)\:\/\/(.[^ \n\<\"\']+)/";
		$this->description = preg_replace_callback($homepage_pattern, function($matches) use ($allowed_protocols) {
			$prefix = $matches[1];
			$protocol = strtolower($matches[2]);
			$url_body = $matches[3];
			$full_url = $protocol . '://' . $url_body;
			if (!in_array($protocol, $allowed_protocols)) {
					return $prefix . htmlspecialchars($full_url, ENT_QUOTES, 'UTF-8'); // 악의적 URL은 링크로 변환하지 않음
			}
			$safe_url = htmlspecialchars($full_url, ENT_QUOTES, 'UTF-8');
			return $prefix . "<a href='{$safe_url}' target='_blank' rel='noopener noreferrer'>{$safe_url}</a>";
		}, ' ' . $this->description);
  }

  # code html highlight
  public function getXHtmlHighlight() : string
  {
    $str = highlight_string($this->description, true);
    $str = preg_replace('#<font color="([^\']*)">([^\']*)</font>#', '<span style="color: \\1">\\2</span>', $str);
    return preg_replace('#<font color="([^\']*)">([^\']*)</font>#U', '<span style="color: \\1">\\2</span>', $str);
  }

  # AI Markdown 파싱 엔진
  public function parseMarkdown() : string
  {
    $text = $this->description;
    $placeholders = []; // 보호할 코드 블록 임시 보관소

    // 1. 멀티라인 & 인라인 코드 블록 선별 추출 (안전한 곳에 보관)
    $text = preg_replace_callback('/```([a-zA-Z0-9]*)\r?\n(.*?)\r?\n```/is', function($matches) use (&$placeholders) {
        $lang = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8'); 
        $id = '@@CODEBLOCK' . count($placeholders) . '@@';
        $placeholders[$id] = "<pre><code class=\"language-{$lang}\">{$code}</code></pre>";
        return $id;
    }, $text);

    $text = preg_replace_callback('/`([^`]+)`/i', function($matches) use (&$placeholders) {
        $id = '@@INLINECODE' . count($placeholders) . '@@';
        $placeholders[$id] = "<code>" . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . "</code>";
        return $id;
    }, $text);

    // 코드 블록을 제외한 '모든 일반 텍스트'의 HTML 태그 무력화
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // 무력화된 텍스트 위에 우리가 허용하는 마크다운 문법만 조심스럽게 적용
    // htmlspecialchars를 거쳤으므로 꺾쇠가 &lt; &gt; 로 변경된 점을 반영하여 정규식 수정
    
    // 제목 (Headers: #, ##, ###)
    $text = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $text);

    // 굵게 (Bold: **text**)
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);

    // 리스트 (Lists: - item)
    $text = preg_replace('/^\- (.*)$/m', '<ul><li>$1</li></ul>', $text);
    $text = preg_replace('/<\/ul>\s*<ul>/', '', $text); 

    // 인용구 (Blockquotes: > text) - 꺾쇠가 &gt; 로 인코딩됨
    $text = preg_replace('/^&gt; (.*)$/m', '<blockquote>$1</blockquote>', $text);

    // 줄바꿈 처리 (태그 밖 줄바꿈)
    $text = preg_replace('/(?<!>)\r?\n(?!<)/', '<br>', $text);

    // 4. 보관해둔 안전한 코드 블록 원상 복구
    $text = strtr($text, $placeholders);

    $this->description = $text;
    return $this->description;
  }

  # 여러형태의 모양
  public function getContext(string $mode='XSS') : string
  {
    $this->description = stripslashes($this->description);
    switch(strtoupper($mode)){
      case 'MARKDOWN': // [NEW] 마크다운 모드 추가
        $this->description = $this->parseMarkdown();
        $this->setAutoLink();
        $this->description = $this->cleanXssTags(); // XSS 방어벽 통과
        break;
      case 'TEXT':
        $this->description = strtr($this->description, ["&nbsp;"=>' ']);
        $this->description = strtr($this->description,["\r\n"=>"\n"]);
        $this->description = $this->setAutoLink();
        $this->allow_tags  = ['<a>'];
        $this->description = $this->cleanTags();
        break;
      case 'XSS':
        $this->description = strtr($this->description,["\r\n"=>"\n"]);
        $this->description = strtr($this->description,["\n"=>"<br>"]);
        $this->description = strtr($this->description,["<br/>"=>"<br>"]);
        $this->description = $this->setAutoLink();
        $this->description = $this->cleanXssTags();
        break;
      case 'HTML':
        $this->description = strtr($this->description,["\r\n"=>"\n"]);
        $this->description = strtr($this->description,["\n"=>"<br>"]);
        $this->description = $this->setAutoLink();
        $this->description = htmlspecialchars($this->description);
        break;
      case 'XHTML':
        $this->description = $this->getXHtmlHighlight();
        $this->description = $this->setAutoLink();
        break;
    }
    return $this->description;
  }

  public function __call(string $query, array $args=[]) : mixed
  {
    $_query = strtolower($query);

    if($_query == 'gettext'){
        return $this->getContext('TEXT');
    }else if($_query == 'getxss'){
        return $this->getContext('XSS');
    }else if($_query == 'gethtml'){
        return $this->getContext('HTML');
    }else if($_query == 'getxhtml'){
        return $this->getContext('XHTML');
    }else if($_query == 'getmarkdown'){
        return $this->getContext('MARKDOWN');
    }else {
      return null;
    }
  }
}