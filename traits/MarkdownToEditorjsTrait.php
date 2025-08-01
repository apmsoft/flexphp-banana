<?php
namespace Flex\Banana\Traits;

/**
 * 마크다운(Markdown) 텍스트를 Editor.js JSON 형식으로 변환하는 트레이트입니다.
 * 지원하는 문법: 헤더(H1-H3), 목록(순서O/X), 이미지, 유튜브, 테이블, 인용문, 코드 블록.
 * Editor.js 변환을 위한 마크다운 예시 (H1)
$plainText = <<<MARKDOWN
# 최종 마크다운 변환 테스트 (H1)

이 문서는 업데이트된 `MarkdownToEditorjsTrait`의 모든 변환 기능을 테스트하기 위한 최종 예제입니다.

## 텍스트 요소 (H2)

기본 문단입니다. 이어서 인용문 예시가 나옵니다.

> 이것은 인용문(Blockquote)입니다. 들여쓰기 된 텍스트는 인용 블록으로 변환되어야 합니다.

### 목록 요소 (H3)

순서가 없는 목록, 있는 목록, 그리고 체크리스트를 모두 지원합니다.

* 순서 없는 목록 항목 1
* 순서 없는 목록 항목 2

1. 순서 있는 목록 항목 1
2. 순서 있는 목록 항목 2

- [x] 완료된 할 일
- [ ] 아직 하지 않은 일
- [ ] 세 번째 할 일

## 미디어 및 임베드

URL 이미지, Base64 인코딩된 이미지, 유튜브 비디오를 테스트합니다.

!https://en.wikipedia.org/wiki/Image(https://placehold.co/600x400/7e22ce/white?text=URL+Image)

![Base64 이미지](data:image/png;base64,iVBORw0KGgoAAAANSUh)

유튜브 영상:
https://www.youtube.com/watch?v=dQw4w9WgXcQ

## 코드 및 데이터

PHP 코드 블록과 헤더가 있는 테이블, 없는 테이블을 테스트합니다.

```php
<?php
// 코드 블록 예시
namespace App\Example;

class TestClass
{
    public function runTest(): bool
    {
        return true;
    }
}
```

### 헤더가 있는 테이블

| ID | 제품명 | 가격 |
|:---|:---|---:|
| 1 | 노트북 | 1,500,000 |
| 2 | 마우스 | 25,000 |

### 헤더가 없는 테이블

| 첫 번째 값 | 두 번째 값 |
|---|---|
| 세 번째 값 | 네 번째 값 |
MARKDOWN;


$markdownToEditorjsTrait = new MarkdownToEditorjsTrait();
$editorJsonArgs = $markdownToEditorjsTrait->build($plainText);
 */
trait MarkdownToEditorjsTrait
{
  public function markdownToEditorjs(string $text, string $version = '2.31.0-rc.7'): array
  {
    $lines = preg_split('/\r?\n/', $text);
    $blocks = [];
    $listItems = [];
    $isList = false;
    $listStyle = 'unordered';
    $i = 0;

    while ($i < count($lines)) {
      $line = $lines[$i];

      // 빈 줄
      if (trim($line) === '') {
        if ($isList) {
          $this->_list($blocks, $listItems, $listStyle);
          $isList = false;
        }
        $i++;
        continue;
      }

      // 코드 블록 (여러 줄)
      if (strpos(trim($line), '```') === 0) {
        if ($isList) { $this->_list($blocks, $listItems, $listStyle); $isList = false; }
        $blocks[] = $this->_codeBlock($lines, $i);
        continue;
      }

      // 테이블
      if (preg_match('/^\|.*\|$/', trim($line))) {
        $tableBlock = $this->_tableBlock($lines, $i);
        if ($tableBlock) {
          if ($isList) { $this->_list($blocks, $listItems, $listStyle); $isList = false; }
          $blocks[] = $tableBlock;
          continue;
        }
      }

      // 체크리스트
      if (preg_match('/^- \[( |x)\]\s+/i', trim($line))) {
        if ($isList) { $this->_list($blocks, $listItems, $listStyle); $isList = false; }
        $blocks[] = $this->_checklistBlock($lines, $i);
        continue;
      }

      // 헤더
      if ($this->_header(trim($line), $blocks)) {
        if ($isList) { $this->_list($blocks, $listItems, $listStyle); $isList = false; }
        $i++;
        continue;
      }
      
      // 인용문
      if ($this->_blockquote(trim($line), $blocks)) {
        if ($isList) { $this->_list($blocks, $listItems, $listStyle); $isList = false; }
        $i++;
        continue;
      }

      // 이미지
      if ($this->_image(trim($line), $blocks)) {
        $i++;
        continue;
      }

      // 유튜브
      if ($this->_youTube(trim($line), $blocks)) {
        $i++;
        continue;
      }

      // 리스트 (순서 O/X)
      if (preg_match('/^(\*|\d+\.)\s+(.+)$/', trim($line), $matches)) {
        $newStyle = ($matches[1] === '*') ? 'unordered' : 'ordered';
        if ($isList && $newStyle !== $listStyle) {
          $this->_list($blocks, $listItems, $listStyle);
        }
        $listItems[] = $matches[2];
        $isList = true;
        $listStyle = $newStyle;
        $i++;
        continue;
      }

      // 일반 문단
      if ($isList) { $this->_list($blocks, $listItems, $listStyle); $isList = false; }
      $this->_paragraph(trim($line), $blocks);
      $i++;
    }

    // 마지막 줄 처리
    if ($isList) {
      $this->_list($blocks, $listItems, $listStyle);
    }

    return [
      'time' => round(microtime(true) * 1000),
      'blocks' => $blocks,
      'version' => $version
    ];
  }

  private function _header(string $line, array &$blocks): bool
  {
    if (preg_match('/^(#{1,3})\s*(.+)$/', $line, $matches)) {
      $blocks[] = ['type' => 'header', 'data' => [ 'text' => $matches[2], 'level' => min(strlen($matches[1]), 3) ]];
      return true;
    }
    return false;
  }

  private function _image(string $line, array &$blocks): bool
  {
    if (preg_match('/!\[(.*?)\]\((.*?)\)/', $line, $matches)) {
      $url = $matches[2];
      $caption = $matches[1];
      $base64Url = '';

      if (strpos($url, 'data:image') === 0) {
        $base64Url = $url;
      } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
        $imageData = @file_get_contents($url);
        if ($imageData !== false) {
          $finfo = new \finfo(FILEINFO_MIME_TYPE);
          $mimeType = $finfo->buffer($imageData);
          $base64Url = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
      }

      if (!empty($base64Url)) {
        $blocks[] = ['id' => uniqid(), 'type' => 'simpleImage', 'data' => ['url' => $base64Url, 'caption' => $caption, 'withBorder' => false, 'withBackground' => false, 'stretched' => false]];
        return true;
      }
    }
    return false;
  }

  private function _youTube(string $line, array &$blocks): bool
  {
    if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $line, $matches)) {
      $videoId = $matches[1];
      $blocks[] = ['type' => 'embed', 'data' => ['service' => 'youtube', 'source' => '[https://www.youtube.com/watch?v=](https://www.youtube.com/watch?v=)' . $videoId, 'embed' => '[https://www.youtube.com/embed/](https://www.youtube.com/embed/)' . $videoId, 'width' => 640, 'height' => 360, 'caption' => '']];
      return true;
    }
    return false;
  }

  private function _list(array &$blocks, array &$listItems, string $style): void
  {
    if (count($listItems) > 0) {
      $blocks[] = ['type' => 'list', 'data' => [ 'style' => $style, 'items' => $listItems ]];
      $listItems = [];
    }
  }
  
  private function _blockquote(string $line, array &$blocks): bool
  {
    if (preg_match('/^>\s*(.+)$/', $line, $matches)) {
      $blocks[] = ['type' => 'quote', 'data' => ['text' => $matches[1], 'caption' => '', 'alignment' => 'left']];
      return true;
    }
    return false;
  }

  private function _codeBlock(array $lines, int &$i): array
  {
    $i++; 
    $codeLines = [];
    while ($i < count($lines)) {
      $line = $lines[$i];
      if (strpos(trim($line), '```') === 0) {
        $i++;
        break;
      }
      $codeLines[] = $line;
      $i++;
    }
    return ['type' => 'code', 'data' => [ 'code' => implode("\n", $codeLines) ]];
  }

  private function _checklistBlock(array $lines, int &$i): array
  {
    $items = [];
    while ($i < count($lines)) {
      $line = trim($lines[$i]);
      if (preg_match('/^- \[( |x)\]\s+(.+)$/i', $line, $matches)) {
        $items[] = [
          'text' => $matches[2],
          'checked' => strtolower($matches[1]) === 'x'
        ];
        $i++;
      } else {
        break;
      }
    }
    return ['type' => 'checklist', 'data' => ['items' => $items]];
  }

  private function _paragraph(string $text, array &$blocks): void
  {
    $blocks[] = ['type' => 'paragraph', 'data' => [ 'text' => $text ]];
  }

  private function _tableBlock(array $lines, int &$i): ?array
  {
    $tableRows = [];
    while ($i < count($lines)) {
      $line = trim($lines[$i]);
      if (!preg_match('/^\|.*\|$/', $line)) {
        break;
      }
      $tableRows[] = array_map('trim', explode('|', trim($line, '|')));
      $i++;
    }

    $withHeadings = false;
    if (count($tableRows) >= 2) {
      $isSeparator = true;
      foreach ($tableRows[1] as $cell) {
        if (!preg_match('/^:?-+:?$/', trim($cell))) {
          $isSeparator = false;
          break;
        }
      }
      if ($isSeparator) {
        unset($tableRows[1]);
        $tableRows = array_values($tableRows);
        $withHeadings = true;
      }
    }

    return count($tableRows) > 0 ? ['type' => 'table', 'data' => [ 'withHeadings' => $withHeadings, 'content' => $tableRows ]] : null;
  }
}