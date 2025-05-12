# Changelog
## Banana[3.5.1]
### - 2025-05-12
- QueryDeleteBasicTask.php update
- TaskJsonAdapter 클래스 업데이트 ( 변수를 참조해야 하는 배열 함수 기능활성화 array_pop...)

### - 2025-05-11
- TaskJsonAdapter class update (if,go,switch 기능 추가)
- Query*Task 클래스들 업데이트

## Banana[3.4.0]
### - 2025-05-11
- TaskJsonAdapter class update (if,go 기능 추가)
- ExceptionBaskTask 예외처리용 클래스 추가
- TaskJsonAdpater ENV,DEFINE,R::* static global 변수에 바로 접근할 수 있도록 예약어 추가
- TaskJsonAdpater enum 클래스내 method 접근할 수 있도록 기능 추가

### - 2025-05-10
- QueryBasicTask remove
- QuerySelectBasicTask new add
- TaskFlow 관련 Task 클래스 정식 출시

## Banana[3.3.0]
### - 2025-05-09
- TaskFlow 용 RequestedFetchBasicTask 클래스 추가
- TaskFlow 용 QueryWhereCaseBasicTask 클래스 추가
- TaskFlow 용 TaskJosnAdapter 클래스 업데이트 @enums::CategoryEnum , @enums::CategoryEnum() 기능 추가
- QueryWhereCaseBasicTask 클래스 update
- QueryBasicTask 클래스 update (by deldel07)
- PagingRelationBasicTask new add
- TotalRecordBasicTask new add
- PagingRelationBasicTask upate

### - 2025-05-08
- TaskFlow 관련 클래스들 기능성 향상을 향한 마이너 업데이트
- QueryInsertBasicTask v0.2.0 -> v0.2.1

### - 2025-05-07
- TaskFlow용 task/Apdapter/JsonAdapter adapters 패키지로 이동
- JsonAdapter -> TaskJosnAdapter 로 클래스명 변경

### - 2025-05-02
- TaskFlow taskFlow 용 아답터 클래스 추가 기능, 모든 아답터 클래스는 process() 메소드로 실행
- TaskFlow용 task/Apdapter/JsonAdapter 추가
- JsonAdapter json 으로 작성된 WorkFlow 를 동작 시킬 수 있는 기능

## Banana[3.2.1]
### - 2025-04-29
- JsonDecoder 클래스 추가 장점 : 여러 \\\로 되어 있거나 중첩 같은 것을 json decode 할 수 있도록 기능 추가

## Banana[3.2.0]
### - 2025-04-22
- util/Requested by reactphp ServerRequestInterface 확장용 클래스 업데이트 v1.0 -> v1.1.0 with 관련 메소드 등 기능 복제 가능하도록 업데이트

### - 2025-04-22
- Flex\Banana\Task QueryBasicTask,QueryDeleteBasicTask,QueryInsertBasicTask,QueryUpdateBasckTask,ValidationBasicTask 정의 및 업데이트
- TaskFlow 버전 패치
- Task 클래스들 버그 패치

### - 2025-04-21
- Flex\Banana\Task 패키지 추가
- Task basic 클래스들 기본 추가 (페이징,쿼리,토탈,데이터체크)

### - 2025-04-17
- FidInterface deprecated
- FidTrait 추상화 메소드 재 정의 및 업데이트

### - 2025-04-16
- NullableValidationTrait 클래스 추가 FormValidation Enum 클래스에서 Null 체크 여부 옵션용 trait 클래스
- NullableValidationTrait 클래스 업데이트

### - 2025-04-14
- FidProviderInterface 메소드 파라마터 추가 가능 하도록 업데이트
- EnumInterface 추가, filter, format, validate 선언 및 null 허용으로 업데이트

### - 2025-04-12
- TaskFlow class 에서 ArrayHelper 제거 복잡성 제거 목적
- TaskFlow 에 Model 클래스 상속시켜 중복성 제거 및 Model 클래스 기능을 그대로 상속 시켜 변수 컨트롤 확장성 극대화 시킴

### - 2025-04-11
- TaskFlow class 추가
- TaskFlow 에서 변수로 담거나 가지고 오거나 있거나 삭제 및 전체 데이터 가공을 편하게 할 수 있도록 업데이트

## Banana[3.1.5]
### - 2025-04-08
- 컬럼 데이터 플러그인 클래스 traits [DelimitedString,PasswordHash,TimeZone, UniqueId] 추가

### - 2025-03-25
- File/Upload.php 연속공백제거 버그 수정 v2.2.3

### - 2025-03-18
- Cipher/AES256Hash class v1.0 -> v1.0.1 로 업데이트
- Cipher/AES256Hash class v1.0.1 -> v1.0.2 로 업데이트

### - 2025-02-12
- UUID v7( string|int $prekey=null ) 기능 업데이트 파라메터 추가 가능

### - 2025-01-24
- Db 클래스 delete() 메소드 부분 버그 패치

### - 2025-01-21
- UUID v7() 기능 추가 (시간순 정렬이 가능한 uuid 알고리즘)

### - 2025-01-14
- Memcached 사용한 CachedMem 클래스 추가

## Banana[3.1.4]
### - 2024-12-21
- HttpRequest() 클래스 업데이트 결과 값을 콜백 및 리턴으로 받을 수 있도록 업데이트

### - 2024-12-03
- Psr-4 규격에 따라 classes 폴더 및의 하부 디렉토리명도 대문자로 시작하도록 변경

## Banana[3.1.3]
### - 2024-11-11
- WhereCouch, DbCouch 파티션 최신 버전에서 파티션을 table 처럼 처리할 수 있도록 업데이트
- UuidGenerator v4 시계열 정렬이 가능한 키 구성이 가능하도록 업데이트 asc,desc 를 구현할 수 있는 UUID 키를 생성할 수 있음

## Banana[3.1.2]
### - 2024-11-09
- DbCouch Multi [insert, updat, delete] 최적화 기능 향상

## Banana[3.1.1]
### - 2024-11-09
- DbCouch Multi Query, Multi execute [insert, updat, delete] 기능 추가

## Banana[3.1.0]
### - 2024-11-08
- DbCouch,WhereCouch,QueryBuilderAbstractCouch CouchDB 이용 클래스 추가
- Db 관련 클래스 전체 업데이트 및 구조 설계 업데이트

## Banana[3.0.9]
### - 2024-11-05
- adpaters/DbSqlAapter -> DbAdapter 데이터베이스 전체용임을 명시하는 이름으로 변경 및 업데이트
- classes/db/DbSqlInterface -> DbInterface 데이터베이스 전체용임을 명시하는 이름으로 변경 미 업데이트
- classes/db/QueryBuilderAbstract -> SqlQueryBuilderAbstract 데이터베이스 SQL 용임을 명시하는 이름으로 변경 미 업데이트
- WhereHelper 일반 클래스에서 제네릭 클래스로 변경
- WhereHelper-> WhereSql SQL 전용임으로 명시
- WhereSqlInterface -> WhereInterface 로 db 전체 interface 이름으로 변경
- 관련 DbMysql,DbPgSql 클래스 업데이트

## Banana[3.0.8]

### - 2024-11-05
- HttpRequest class get,post,첨부파일 외 put, patch, delete 사용성 추가

## Banana[3.0.7]

### - 2024-11-04
- DbManager 를 제네릭 클래스로 변경 , DbMySql,DbPgSql 클래스 등으로 전문성 있게 분리
- DnsBuilder class remove

## Banana[3.0.6]

### - 2024-11-01
- DbManager class 부분 업데이트,DbSqlInterface connect, selectDb method 추가

### - 2024-10-17
- DbMysqli class deprecated
- Multi DbManger 클래스 추가 (MySql,PostgreSql 지원 PDO)

## Banana[3.0.5]

### - 2024-10-17
- R class sysmsg, strings, numbers, arrays, tables 으로 전체 통합

### - 2024-10-14
- Log class self 패치

## Banana[3.0.4]

### - 2024-10-14
- autoload 의존성 문제 해결

### - 2024-10-11
- R class 최적화 및 클래스 캐시 기능 추가
- StringTools 클래스 기능 강화

### - 2024-10-10
- DbMySqli, R class 의존성 define 변수 제거
- 클래스 파일들 버그 패치 및 업데이트