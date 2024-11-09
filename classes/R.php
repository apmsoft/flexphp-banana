<?php
namespace Flex\Banana\Classes;

use ArrayObject;
use Exception;
use JsonException;

final class R
{
    public const __version = '2.3.2';
    public static string $language = ''; // 국가코드

    # resource 값
    public static array $sysmsg   = [];
    public static array $strings  = [];
    public static array $arrays   = [];
    public static array $tables   = [];
    public static array $numbers  = [];

    private static array $cache = [];

    # 배열값 추가 등록
    public static function init(string $lang = ''): void
    {
        self::$language = trim($lang);
    }

    # 특정 리소스 키에 해당하는 값 리턴
    protected static function get(string $query, string $fieldname): mixed
    {
        $cacheKey = "{$query}_{$fieldname}_" . self::$language;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $target = self::getTarget($query);
        $result = $target[self::$language][$fieldname] ?? null;

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    # 특정 리소스에 전체 값 바꾸기
    public static function set(string $query, array $data): void
    {
        $target = &self::getTarget($query);
        $target[self::$language] = $data;
        self::clearCache($query);
    }

    protected static function fetch(string $query): array
    {
        $cacheKey = "{$query}_fetch_" . self::$language;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $target = self::getTarget($query);
        $result = $target[self::$language] ?? [];

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    # 특정리소스의 키에 해당하는 값들을 배열로 돌려받기
    protected static function selectR(array $params): array
    {
        $cacheKey = md5(serialize($params) . self::$language);
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $argv = [];
        foreach ($params as $query => $fieldname) {
            $columns = str_contains($fieldname, ",") ? explode(",", $fieldname) : [$fieldname];
            foreach ($columns as $columname) {
                $argv[$columname] = self::get($query, trim($columname));
            }
        }

        self::$cache[$cacheKey] = $argv;
        return $argv;
    }

    public static function __callStatic(string $query, array $args = []): mixed
    {
        return match(true) {
            strtolower($query) === 'dic' && !empty($args) => (object)$args[0],
            $query === 'fetch' && isset($args[0]) && is_string($args[0]) => self::fetch($args[0]),
            $query === 'select' && !empty($args) => self::selectR($args[0]),
            isset($args[0]) && is_string($args[0]) => self::get($query, $args[0]),
            isset($args[0]) && is_array($args[0]) => self::mergeData($query, $args[0]),
            default => null
        };
    }

    private static function &getTarget(string $query): array
    {
        if (in_array($query, ['sysmsg', 'strings', 'numbers', 'arrays', 'tables'])) {
            return self::${$query};
        }
    }

    # 배열값 추가 머지
    private static function mergeData(string $query, array $args): void
    {
        $target = &self::getTarget($query);
        $target[self::$language] = ($target[self::$language] ?? []) + $args;
        self::clearCache($query);
    }

    # 데이터 로딩된 상태인지 체크
    private static function is(string $query): bool
    {
        $target = self::getTarget($query);
        return isset($target[self::$language]);
    }

    public static function parser(string $filename, string $query): void
    {
        if (!$query) {
            throw new Exception(__CLASS__ . ' :: ' . __LINE__ . ' ' . $query . ' is null');
        }

        if (!self::is($query)) {
            $real_filename = self::findLanguageFile($filename);
            $storage_data = file_get_contents($real_filename);
            if ($storage_data) {
                $data = self::filterJSON($storage_data, true);
                if (!is_array($data)) {
                    throw new Exception(__CLASS__ . ' :: ' . __LINE__ . ' ' . $real_filename . ' / ' . $data);
                }
                self::mergeData($query, $data);
            }
        }
    }

    public static function filterJSON(string $json, bool $assoc = false, int $depth = 512, int $options = 0): mixed
    {
        $json = preg_replace(['/\/\/.*$/m', '/\/\*.*?\*\//s'], '', $json);
        $json = preg_replace('/\s+/', ' ', $json);
        $json = preg_replace('/([{,])(\s*)([^"]+?)\s*:/', '$1"$3":', $json);

        try {
            return json_decode($json, $assoc, $depth, JSON_THROW_ON_ERROR | $options);
        } catch (JsonException $e) {
            return $e->getMessage();
        }
    }

    public static function findLanguageFile(string $filename): string
    {
        $path_parts = pathinfo($filename);
        $nation_filename = sprintf('%s/%s_%s.%s', 
            $path_parts['dirname'], 
            $path_parts['filename'], 
            self::$language, 
            $path_parts['extension']
        );
        return file_exists($nation_filename) ? $nation_filename : $filename;
    }

    private static function clearCache(string $query): void
    {
        foreach (self::$cache as $key => $value) {
            if (strpos($key, $query) === 0) {
                unset(self::$cache[$key]);
            }
        }
    }

    public function __destruct()
    {
        foreach (['sysmsg', 'strings', 'numbers', 'arrays', 'tables', 'r', 'cache'] as $property) {
            unset(self::${$property});
        }
    }
}