<?php
namespace Flex\Banana\Traits;

use Flex\Banana\Classes\Array\ArrayHelper;
use Flex\Banana\Classes\Date\DateTimez;
use \DateTimeZone;

# 날짜 관련 데이터베이스 저장 및 뷰
trait TimeZoneTrait
{
    public function nowInTZ(string $utcgmttime): string
    {
        return (new DateTimez("now", $utcgmttime))->format('Y-m-d H:i:s P');
    }

    public function toTZFormat(string $datetimeptz,  string $utcgmttime, string $convert_utcgmttime, array $timezone_formats) : ?string
    {
        if(!$datetimeptz){
            return null;
        }

        $dataTimeZ = (new DateTimez($datetimeptz, $utcgmttime));
        $dataTimeZ->setTimezone(new DateTimeZone($convert_utcgmttime));
        return $dataTimeZ->format(
            ((new ArrayHelper( $timezone_formats ))->find("timezone",$convert_utcgmttime)->value)['format']
        );
    }
}