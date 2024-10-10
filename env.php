<?php
use Flex\Banana\Classes\App;
use Flex\Banana\Classes\R;

App::init();

# 기본 Validation Resource
R::init(App::$language ?? '');

// vendor/composer/autoload_real.php에 추가
$vendorDir = dirname(dirname(dirname(__FILE__)));
define('FLEXPHP_BANANA_ROOT', $vendorDir . '/apmsoft/flexphp-banana');

// env.php에서 사용
$filePath = FLEXPHP_BANANA_ROOT . '/res/sysmsg.json';
if (file_exists($filePath)) {
    R::parser($filePath, 'sysmsg');
} else {
    throw new Exception("sysmsg.json file not found at: " . $filePath);
}
?>