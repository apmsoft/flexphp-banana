<?php
use Flex\Banana\Classes\App;
use Flex\Banana\Classes\R;

App::init();

# 기본 Validation Resource
R::init(App::$language ?? '');

$reflector = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
$vendorDir = realpath(dirname(dirname($reflector->getFileName())));
$file_sysmsg = $vendorDir . '/apmsoft/flexphp-banana/res/sysmsg.json';
if (file_exists($file_sysmsg)) {
    R::parser($file_sysmsg, 'sysmsg');
} else {
    throw new Exception("ERROR :: sysmsg.json file not found at: " . $file_sysmsg);
}
?>
