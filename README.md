# flexphp-banana

# 메뉴얼
http://flexphp.fancyupsoft.com


# 설치 방법
composer require apmsoft/flexphp-banana:^3.0.2
composer require apmsoft/flexphp-banana:dev-main


# server.php
## App 클래스 실행
App::init();

## resource JSON 자동 로드
R::init(App::$language ?? '');
R::parser('{파일절대경로}/strings.json', 'strings');
R::parser('{파일절대경로}/sysmsg.json', 'sysmsg');
R::parser('{파일절대경로}/arrays.json', 'arrays');
R::parser('{파일절대경로}/tables.json', 'tables');
R::parser('{파일절대경로}/integers.json', 'integers');
R::parser('{파일절대경로}/floats.json', 'floats');
R::parser('{파일절대경로}/holiday.json', 'holiday');
