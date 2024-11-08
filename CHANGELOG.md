# Changelog

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