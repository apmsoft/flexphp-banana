<?php
namespace Flex\Banana\Classes\Db;

# purpose : 각종 SQL 관련 디비를 통일성있게  작성할 수 있도록 틀을 제공
interface DbInterface
{
    public function connect(string $host, string $dbname, string $user, string $password, int $port, string $charset, ?array $options=[]) : self;
    public function selectDB( string $dbname ): self;
    public function whereHelper() : WhereCouch|WhereSql;
    public function query(string $query='', array $params = []) : DbResultSql|DbResultCouch|array;			# 쿼리
    public function insert() : void;	# 저장
    public function update() : void;	# 수정
    public function delete() : void;	# 삭제
}