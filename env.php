<?php
$reflector = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
$vendorDir = realpath(dirname(dirname($reflector->getFileName())));
$file_sysmsg = $vendorDir . '/apmsoft/flexphp-banana/res/sysmsg.json';
if (file_exists($file_sysmsg)) {
    Flex\Banana\Classes\App::init();

    # 기본 Validation Resource
    Flex\Banana\Classes\R::init(Flex\Banana\Classes\App::$language ?? '');
    Flex\Banana\Classes\R::parser($file_sysmsg, 'sysmsg');
} else {
    throw new Exception("ERROR :: sysmsg.json file not found at: " . $file_sysmsg);
}
?>
