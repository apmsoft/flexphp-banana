<?php
namespace Flex\Banana\Classes\Http;
use Flex\Banana\Classes\Log;

class HttpRequest {
    public const __version = '1.4.0'; // 버전 업데이트
    private $urls = [];
    private $mch;

    public function __construct(array $argv = []) {
        if (!is_array($argv)) {
            throw new \Exception(__CLASS__.' :: '.__LINE__.' is not array');
        }
        $this->urls = $argv;
        $this->mch = curl_multi_init();
    }

    /**
     * 요청을 추가합니다. 파라미터를 배열로 전달할 수 있습니다.
     * @param string $url
     * @param string|array $params
     * @param array $headers
     * @return HttpRequest
     */
    public function set(string $url, $params = [], array $headers = []): HttpRequest {
        if (trim($url)) {
            $this->urls[] = [
                "url"     => $url,
                "params"  => $params,
                "headers" => $headers
            ];
        }
        return $this;
    }

    public function get(callable $callback = null) {
        $response = $this->execute('GET');
        if ($callback !== null && is_callable($callback)) {
            $callback($response);
        }
        return $response;
    }

    public function post(callable $callback = null) {
        $response = $this->execute('POST');
        if ($callback !== null && is_callable($callback)) {
            $callback($response);
        }
        return $response;
    }

    public function put(callable $callback = null) {
        $response = $this->execute('PUT');
        if ($callback !== null && is_callable($callback)) {
            $callback($response);
        }
        return $response;
    }

    public function delete(callable $callback = null) {
        $response = $this->execute('DELETE');
        if ($callback !== null && is_callable($callback)) {
            $callback($response);
        }
        return $response;
    }

    public function patch(callable $callback = null) {
        $response = $this->execute('PATCH');
        if ($callback !== null && is_callable($callback)) {
            $callback($response);
        }
        return $response;
    }

    private function execute(string $method) 
    {
        $response = [];
        $ch = []; // $ch 배열 초기화

        foreach ($this->urls as $idx => $requestInfo) 
        {
            $ch[$idx] = curl_init($requestInfo['url']);
            
            curl_setopt($ch[$idx], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch[$idx], CURLOPT_CUSTOMREQUEST, $method);

            $headers = $requestInfo['headers'] ?? [];
            $params = $requestInfo['params'] ?? [];

            $contentType = $this->getContentType($headers);
            if (!$contentType && $method !== 'GET') {
                if (is_string($params) && (strpos($params, '{') === 0 || strpos($params, '[') === 0)) {
                    $contentType = 'application/json';
                } else {
                    $contentType = 'application/x-www-form-urlencoded';
                }
                $headers[] = "Content-Type: $contentType";
            }

            if ($method !== 'GET') {
                $postFields = $this->preparePostFields($params, $contentType);
                curl_setopt($ch[$idx], CURLOPT_POSTFIELDS, $postFields);
            } else if (!empty($params)) {
                $queryString = is_array($params) ? http_build_query($params) : $params;
                $requestInfo['url'] .= (strpos($requestInfo['url'], '?') === false ? '?' : '&') . $queryString;
                curl_setopt($ch[$idx], CURLOPT_URL, $requestInfo['url']);
            }

            curl_setopt($ch[$idx], CURLOPT_HTTPHEADER, $headers);
            curl_multi_add_handle($this->mch, $ch[$idx]);
        }

        do {
            curl_multi_exec($this->mch, $running);
            curl_multi_select($this->mch);
        } while ($running > 0);

        foreach (array_keys($ch) as $index) {
            $httpCode = curl_getinfo($ch[$index], CURLINFO_HTTP_CODE);
            $body = curl_multi_getcontent($ch[$index]);
            $contentTypeHeader = curl_getinfo($ch[$index], CURLINFO_CONTENT_TYPE);
            
            // 각 요청에 맞는 정확한 정보로 로그 기록
            $currentRequest = $this->urls[$index];
            Log::d("[DEBUG CURL]", [
                "httpCode" => $httpCode,
                "body" => $body,
                "headers" => $currentRequest['headers'],
                "params" => $currentRequest['params'],
                "url" => curl_getinfo($ch[$index], CURLINFO_EFFECTIVE_URL), // 실제 요청된 URL 기록
                "responseContentType" => $contentTypeHeader
            ]);

            $decodedBody = $body;
            $isJsonResponse = $contentTypeHeader && stripos($contentTypeHeader, 'application/json') !== false;

            if ($isJsonResponse && is_string($body) && !empty($body)) {
                if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                    try {
                        $decodedBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        Log::e($index, 'JSON decode error', $e->getMessage());
                    }
                } else {
                    $tempDecoded = json_decode($body, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $decodedBody = $tempDecoded;
                    } else {
                        // Fatal Error 방지
                        Log::e($index, 'JSON decode error', json_last_error_msg());
                    }
                }
            }
            
            $response[$index] = [
                'code' => $httpCode,
                'body' => $decodedBody,
                'url' => curl_getinfo($ch[$index], CURLINFO_EFFECTIVE_URL)
            ];
            curl_multi_remove_handle($this->mch, $ch[$index]);
        }

        $this->urls = [];
        return $response;
    }
    
    private function getContentType(array $headers): ?string {
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                return trim(substr($header, strpos($header, ':') + 1));
            }
        }
        return null;
    }

    private function preparePostFields($params, ?string $contentType) {
        if (is_array($params)) {
            if (stripos($contentType, 'application/json') !== false) {
                return json_encode($params, JSON_UNESCAPED_UNICODE);
            }
            if (stripos($contentType, 'multipart/form-data') !== false) {
                // 배열 내 파일 경로(@)를 CURLFile 객체로 변환하는 로직 추가 가능
                return $params;
            }
            // 기본값은 x-www-form-urlencoded
            return http_build_query($params);
        }
        return $params; // 문자열이면 그대로 반환
    }

    public function __destruct() {
        if ($this->mch) {
            curl_multi_close($this->mch);
        }
    }
}