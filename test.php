<?php
// /Users/kimjongkwas/Documents/projects/flexphp-banana/test.php

require_once __DIR__ . '/vendor/autoload.php';

use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Db\DbPgSql;
use Flex\Banana\Classes\Db\WhereSql;
use Flex\Banana\Classes\Log;

/**
 * FlexPHP Banana 프레임워크의 DbManager insert() 패턴 예시 스크립트
 */
class DbInsertExample
{
    public const __version = '1.0.0';

    private DbManager $db;

    public function __construct(DbManager $db)
    {
        $this->db = $db;
    }

    /**
     * 사용자 데이터를 삽입하는 예시 메서드
     *
     * @param string $tableName
     * @param array $userData
     * @return void
     * @throws Exception
     */
    public function insertUser(string $tableName, array $userData): void
    {
        try {
            // 1. 트랜잭션 시작 전 활성화된 트랜잭션 체크 및 롤백
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->db->beginTransaction();

            // 2. ArrayAccess 인터페이스를 통해 insert할 컬럼값 바인딩
            // $this->db['컬럼명'] = 값 형태로 대입합니다.
            $this->db['name'] = $userData['name'] ?? 'Guest';
            $this->db['email'] = $userData['email'] ?? '';
            $this->db['created_at'] = date('Y-m-d H:i:s');

            // 3. PostgreSQL 암호화 예시 (banana_rules.md 지침 참고)
            // (실제 데이터베이스 함수나 암호화 클래스가 적용되어 있다면 사용 가능)
            // $this->db['secure_code'] = "encode(encrypt_iv('data', 'key', 'iv', 'aes'), 'hex')";

            // 4. table()과 insert()를 체이닝하여 쿼리 실행
            $this->db->table($tableName)->insert();

            // 5. 트랜잭션 커밋
            $this->db->commit();

            Log::i("데이터 삽입 성공: " . $userData['name']);

        } catch (Exception $e) {
            // 에러 발생 시 롤백
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Log::e("데이터 삽입 실패: " . $e->getMessage());
            throw new Exception("Insert failed: " . $e->getMessage());
        }
    }
}

// --- 테스트 실행부 ---
try {
    // 1. DB 관련 의존성 주입 객체 생성
    $whereSql = new WhereSql('AND');
    $dbProcessor = new DbPgSql($whereSql);
    
    // 2. DbManager 생성
    $db = new DbManager($dbProcessor);

    // 3. 데이터베이스 연결 (실제 구동 환경에 맞춰 host, dbname, user 등 설정)
    // $db->connect('localhost', 'test_db', 'postgres', 'password', 5432, 'utf8');

    $example = new DbInsertExample($db);
    
    // 실제 삽입 테스트를 하고 싶다면 아래 주석을 풀고 사용하십시오.
    /*
    $example->insertUser('users', [
        'name' => '홍길동',
        'email' => 'hong@example.com'
    ]);
    */
    echo "test.php 스크립트 작성 완료 (DbManager insert 패턴 예시)\n";
} catch (Exception $e) {
    echo "에러 발생: " . $e->getMessage() . "\n";
}
