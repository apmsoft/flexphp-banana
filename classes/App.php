<?php
namespace Flex\Banana\Classes;

# 접속에 따른 디바이스|브라우저등 정보
final class App
{
    public const __version      = '1.2';
    public static $platform     = 'Nan';
    public static $browser      = 'Nan';
    public static $host;
    public static $language     = 'ko';
    public static $locale       = 'ko_KR';
    public static $http_referer = null;
    public static $ip_address   = '';
    public static $protocol     = 'Nan';
    public static $version      = '1.0';

    public static function init() : void
    {
        self::detectPlatformAndBrowser();
        self::setHttpReferer();
        self::setLanguageAndLocale();
        self::setProtocolAndHost();
        self::$ip_address = self::getClientIp();
    }

    private static function detectPlatformAndBrowser() : void
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $platforms = [
            'Linux' => 'Linux',
            'iPod' => 'iPod',
            'iPhone' => 'iPhone',
            'iPad' => 'iPad',
            'Windows Phone' => 'Windows Phone',
            'Windows CE' => 'Windows CE',
            'lgtelecom' => 'lgtelecom',
            'Android' => 'Android',
            'Macintosh' => 'Mac',
            'mac os x' => 'Mac',
            'Windows' => 'Windows',
            'Win32' => 'Windows'
        ];

        foreach ($platforms as $key => $value) {
            if (stristr($agent, $key)) {
                self::$platform = $value;
                break;
            }
        }

        $browsers = [
            'MSIE' => 'Explorer',
            'Firefox' => 'Firefox',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
            'Opera' => 'Opera',
            'Netscape' => 'Netscape'
        ];

        foreach ($browsers as $key => $value) {
            if (stristr($agent, $key) && ($key !== 'MSIE' || !stristr($agent, 'Opera'))) {
                self::$browser = $value;
                break;
            }
        }
    }

    private static function setHttpReferer() : void
    {
        self::$http_referer = $_SERVER['HTTP_REFERER'] ?? null;
    }

    private static function setLanguageAndLocale() : void
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $hal = explode(',', strtr($_SERVER['HTTP_ACCEPT_LANGUAGE'], [';' => ',', '-' => '_']));
            foreach ($hal as $v) {
                if (strpos($v, self::$language . '_') !== false) {
                    self::$locale = $v;
                    break;
                }
            }
            self::$language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }
    }

    private static function setProtocolAndHost() : void
    {
        self::$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? 'https' : 'http';
        self::$host = isset($_SERVER['HTTP_HOST']) ? self::$protocol . "://" . $_SERVER['HTTP_HOST'] : '';
    }

    public static function getClientIp() : string
    {
        $ipSources = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipSources as $source) {
            if (isset($_SERVER[$source])) {
                return $_SERVER[$source];
            }
        }

        return '';
    }
}
?>