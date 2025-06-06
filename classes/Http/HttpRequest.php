<?php
namespace Flex\Banana\Classes\Http;
use Flex\Banana\Classes\Log;

class HttpRequest {
    public const __version = '1.3.0';
    private $urls = [];
    private $mch;

    public function __construct(array $argv = []) {
        if (!is_array($argv)) {
            throw new \Exception(__CLASS__.' :: '.__LINE__.' is not array');
        }
        $this->urls = $argv;
        $this->mch = curl_multi_init();
    }

    public function set(string $url, string $params, array $headers = []): HttpRequest {
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
        foreach ($this->urls as $idx => $url) 
        {
            $ch[$idx] = curl_init($url['url']);

            $headers = $url['headers'] ?? [];
            $params = $url['params'];

            curl_setopt($ch[$idx], CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch[$idx], CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch[$idx], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch[$idx], CURLOPT_RETURNTRANSFER, true);

            $contentType = $this->getContentType($headers);

            if ($method !== 'GET') {
                $postFields = $this->preparePostFields($params, $contentType);
                curl_setopt($ch[$idx], CURLOPT_POSTFIELDS, $postFields);
            } else if ($params) {
                $url['url'] .= (strpos($url['url'], '?') === false ? '?' : '&') . $params;
                curl_setopt($ch[$idx], CURLOPT_URL, $url['url']);
            }

            if (!$this->hasContentTypeHeader($headers) && $contentType) {
                $headers[] = "Content-Type: $contentType";
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
            $isJsonResponse = stripos($contentTypeHeader, 'application/json') !== false;

            // 이미 배열인지 확인
            if (is_array($body)) {
                $decodedBody = $body;
            } else if (is_string($body) && !empty($body)) {
                if ($isJsonResponse) {
                    // JSON 디코딩 시도
                    $decodedBody = $body; // 기본값으로 원본 설정
                    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                        try {
                            $tempDecoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                            if (is_array($tempDecoded)) {
                                $decodedBody = $tempDecoded;
                            }
                        } catch (\JsonException $e) {
                            Log::e($index, 'JSON decode error', $e->getMessage());
                            throw new \Exception(json_encode(['message' => $e->getMessage(),'body' => $body]));
                        }
                    } else {
                        $tempDecoded = json_decode($body, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($tempDecoded)) {
                            $decodedBody = $tempDecoded;
                        } else {
                            Log::e($index, 'JSON decode error', json_last_error_msg());
                            throw new \Exception(json_encode(['message' => $e->getMessage(),'body' => json_last_error_msg()]));
                        }
                    }
                } else {
                    $decodedBody = $body;
                }
            } else {
                $decodedBody = $body;
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

    private function getContentType($headers): ?string {
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                list(, $contentType) = explode(':', $header, 2);
                return trim($contentType);
            }
        }
        return null;
    }

    private function hasContentTypeHeader($headers): bool {
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                return true;
            }
        }
        return false;
    }

    private function preparePostFields($params, $contentType) {
        switch ($contentType) {
            case 'application/json':
                return $params; // JSON string as is
            case 'application/x-www-form-urlencoded':
                return $params; // URL encoded string as is
            case 'multipart/form-data':
                parse_str($params, $parsedParams);
                $postFields = [];
                foreach ($parsedParams as $key => $value) {
                    if (is_string($value) && strpos($value, '@') === 0 && file_exists(substr($value, 1))) {
                        $filePath = substr($value, 1);
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $filePath);
                        finfo_close($finfo);
                        $fileName = basename($filePath);
                        $postFields[$key] = new \CURLFile($filePath, $mimeType, $fileName);
                    } else {
                        $postFields[$key] = $value;
                    }
                }
                return $postFields;
            default:
                return $params;
        }
    }

    public function __destruct() {
        curl_multi_close($this->mch);
    }
}