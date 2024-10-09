# flexphp-banana
플랙스php
서버 사이드 프로그래밍 언어 
PHP 8.1+

# template 파일 및 첨부파일 업로드 루트 폴더
_data 

# chmod -R 707 _data

# 폴더 구조
## 클래스
- classes : banana 클래스
- columns : 퀄럼정의 및 퀄럼전용 클래스
- components : adapter , interface, dataProcessing 클래스 모음
- servie : 실제 서비스용 클래스 작업실
- topadm : 실제 관리자용 클래스 작업실
- util : 유틸 클래스 모음

## 환경설정
- config : 환경설정 정의 파일

## 리소스 다국어, 배열등
- res : json 파일 모음

## 함수
- function : 함수 등록

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
        "flexphp/annona": "dev-main" # 최신버전 설치
    }
}

# command line : 최신 버전으로받기
composer require flexphp/banana:^dev-main

# command line : 버전 명시 해서 받기
composer require flexphp/banana:^3.0
