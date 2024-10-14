# flexphp-banana

# 메뉴얼
http://flexphp.fancyupsoft.com


# 설치 방법
composer require apmsoft/flexphp-banana:^3.0
composer require apmsoft/flexphp-banana:dev-main


# server.php
## App 클래스 실행
use Flex\Banana\Classes\App;

App::init();

## resource JSON 자동 로드
use Flex\Banana\Classes\R;

R::init(App::$language ?? '');
R::parser('{파일절대경로}/strings.json', 'strings');
R::parser('{파일절대경로}/sysmsg.json', 'sysmsg');
R::parser('{파일절대경로}/arrays.json', 'arrays');
R::parser('{파일절대경로}/tables.json', 'tables');
R::parser('{파일절대경로}/integers.json', 'integers');
R::parser('{파일절대경로}/floats.json', 'floats');
R::parser('{파일절대경로}/holiday.json', 'holiday');

기본 sysmsg.json 메세지
{
    "e_null": "을(를) 입력 하세요",
    "e_spaces": "공백없이 입력 하세요",
    "e_enum": "에 해당하는 값을 찾을 수 없습니다.",
    "e_ctype_alnum": "영문 또는 숫자만 입력하세요",
    "e_same_repeat_string" : "연속된 문자를 %s자 이상 입력할 수 없습니다.",
    "e_number": "숫자만 입력하세요",
    "e_korean": "한글을 입력할 수 없습니다",
    "e_string_length": "길이는 %d~%d자를 입력하세요",
    "e_etc_string": "허용된 특수문자%s 외에는 입력할 수 없습니다",
    "e_chk_etc_string": "특수문자를 최소 1개 이상 입력하세요",
    "e_alphabet": "영어(alphabet)을 입력 하세요",
    "e_date": "날짜를 정확하게 입력 하세요",
    "e_time": "시간을 정확하게 입력 하세요",
    "e_date_period": "날짜 기간을 정확하게 입력 하세요",
    "e_equals": "일치하지 않습니다.",
    "e_up_alphabet": "대문자로 입력 하세요",
    "e_low_alphabet": "소문자로 입력 하세요",
    "e_first_alphabet":"첫 글자는 영문으로만 입력 하세요",
    "e_json":"데이터를 JSON 형태로 입력 하세요",
    "e_float": "숫자와 소수형 숫자만 입력하세요",
    "e_link_url": "URL 주소 정확하게 입력 하세요"
}