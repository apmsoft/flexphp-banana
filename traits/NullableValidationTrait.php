<?php
namespace Flex\Banana\Traits;

use Flex\Banana\Classes\Request\FormValidation as Validation;

trait NullableValidationTrait
{
    /**
     * 공통 optional 유효성 검사 메서드
     *
     * @param string $column_name   컬럼 이름 (예: DB 컬럼명)
     * @param string $column_title  컬럼 제목 (예: 사용자 노출용 이름)
     * @param mixed $data           검증할 데이터
     * @param mixed ...$params      추가 파라미터 ('optional' 또는 '?' 등)
     *
     * @return Validation
     */
    public function checkOptional(string $column_name, string $column_title, mixed $data = null, ...$params): Validation
    {
        $validation = new Validation($column_name, $column_title, $data);

        if (!in_array($params[0] ?? null, ['optional', '?'], true)) {
            $validation->null();
        }

        return $validation;
    }
}