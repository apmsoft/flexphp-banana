# flexphp-banana

# 메뉴얼
http://flexphp.fancyupsoft.com


# 설치 방법
## composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/apmsoft/flexphp-banana"
        }
    ],
    "require": {
        "react/http": "^1.10.0",
        "react/async":"^4.3.0",
        "nikic/fast-route": "^1.3",
        "spatie/async": "^1.5",
        "flexphp/banana": "dev-main"
    }
}

# server.php
## App 클래스 실행
App::init();

## resource JSON 자동 로드
R::init(App::$language ?? '');
R::__autoload_resource([
    _VALUES_  => ['sysmsg','strings','integers','arrays']
]);
