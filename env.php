<?php
use Flex\Banana\Classes\App;
use Flex\Banana\Classes\R;

App::init();

# 기본 Validation Resource
R::init( App::$language ?? '');
R::parser(__DIR__.'/../res/sysmsg.json', 'sysmsg');
?>