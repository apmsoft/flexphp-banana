<?php
use Flex\Banana\R;
use Flex\Banana\App;

# root 경로
define('_ROOT_PATH_',__DIR__.'/..');

# 기본 설정
define('_LIBS_','libs');            #PHP 외부라이브러리

# 리소스
define('_RES_','res');
define('_QUERY_','res/query');       #테이블명 및 쿼리문
define('_VALUES_','res/values');     #데이터 타입이 확실한
define('_RAW_','res/raw');           #가공되지 않은 원천 내용

# 데이터 업로드 및 캐슁파일
define('_DATA_','_data');           #파일업로드 및 캐슁파일 위치(707 또는 777)
define('_UPLOAD_','_data/files');   #첨부파일등

# 데이타베이스 정보
define('_DB_SHA2_ENCRYPT_KEY_','sfsfsfsafsafsfwarfdgjgejgesfsfsafksdfsfsvsvxzvasfddaaerdsadsgd');
define('_DB_HOST_','mysql-master');
define('_DB_USER_','test');
define('_DB_PASSWD_','test!@!@');
define('_DB_NAME_','test_db');
define('_DB_PORT_',3306);

# 기본 선언 클래스 /-------------------
App::init();

# resource JSON 자동 로드 /---------------
R::init(App::$language ?? '');
R::__autoload_resource([
    _VALUES_  => ['sysmsg','strings','integers','arrays']
]);
?>
