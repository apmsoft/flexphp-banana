FlexPHP Banana 문법 및 클래스 참조 가이드 (banana_rules.md)
이 문서는 FlexPHP Banana 프레임워크의 핵심 문법 규칙 및 주요 클래스 구조를 정의합니다. Gemini는 이 규칙에 따라 PHP 코드를 검사해야 합니다.

1. 프레임워크 핵심 설계 원칙
네임스페이스: 기본적으로 Flex\Banana 네임스페이스를 사용하며, 용도에 따라 Adapters, Classes, Interfaces, Task, Traits 등으로 구분됩니다. 
버전 관리: 모든 주요 클래스는 public const __version 또는 _version을 포함하여 버전을 명시해야 합니다. 
비동기 지향: ReactPHP 및 Swoole 환경에서 동작하도록 설계되었으며, TaskFlow를 통한 비차단 로직 구성을 권장합니다. 

2. 주요 클래스 및 문법 규칙
A. DB 핸들링 (Flex\Banana\Classes\Db)
PostgreSQL 전용 기능: CipherPgsqlAes, CipherPgsqlAes256Cbc 등의 클래스를 통해 암호화 처리를 수행합니다. 
Query Builder: select, table, where, orderBy, limit 등의 메서드 체이닝을 지원합니다. 
where 조건 시 배열 형태 ['field', 'operator', 'value'] 지원. 
암호화 메서드: encrypt(string $column), decrypt(string $column)를 사용하여 DB 레벨의 보안을 유지합니다. 

B. 데이터 및 유틸리티 (Flex\Banana\Classes\Array, Json, Log)
ArrayHelper: 멀티 배열 검색(find, findWhere), 정렬(sorting), 특정 키 추출(pluck) 등의 기능을 체이닝 방식으로 사용합니다. 
Log 관리: Log::d, Log::v, Log::i, Log::w, Log::e를 사용하여 디버그 정보를 기록하며, 파일 또는 화면 출력을 설정할 수 있습니다. 
JSON 처리: JsonEncoder::toJson 및 JsonDecoder::toArray를 사용하여 안정적인 데이터 변환을 수행합니다. 

C. 워크플로우 제어 (Flex\Banana\Adapters\TaskJsonAdapter)
Step 기반 실행: method, function, class 등의 스텝 타입을 정의하여 실행 흐름을 제어합니다. 
조건 분기: switch나 if 타입을 사용하여 실행 로직을 동적으로 분기할 수 있습니다. 
환경 변수 및 상수 보호: 보안을 위해 bannedEnvVars 및 bannedConstants를 설정하여 접근을 제한합니다. 

3. 문법 체크 대상 (Gemini가 검토할 항목)
클래스 선언: 상수 버전(const __version)이 포함되어 있는가? 
데이터 타입: 메서드 인자 및 리턴 값에 적절한 타입 힌팅(string, int, array, void 등)이 적용되어 있는가? 
비동기 적합성: Swoole이나 ReactPHP 환경에서 문제를 일으킬 수 있는 동기식 블로킹 함수(예: 무거운 파일 연산 등)가 무분별하게 사용되지 않았는가?