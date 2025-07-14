<?php
namespace Flex\Banana\Traits;

use Flex\Banana\Classes\Array\ArrayHelper;
use Flex\Banana\Classes\Date\DateTimez;
use \DateTimeZone;

# 날짜 관련 데이터베이스 저장 및 뷰
trait TimeZoneTrait
{
	public function nowInTZ(
		string $utcgmttime
	): string
	{
		return (new DateTimez("now", $utcgmttime))
		->format('Y-m-d H:i:s P');
	}

	public function toTZFormat(
		string $datetimeptz,  
		string $from, 
		string $to, 
		array|string $timezone_formats
	) : ?string
	{
		if(!$datetimeptz){
			return null;
		}

		$dataTimeZ = (new DateTimez($datetimeptz, $from));
		$dataTimeZ->setTimezone(new DateTimeZone($to));

		$format = '';
		if(is_array($timezone_formats)){
			$format = ((new ArrayHelper( $timezone_formats ))->find("timezone",$to)->value)['format'];
		}else if(is_string($timezone_formats)){
			$format = $timezone_formats;
		}

		if(!$format)
			return null;

		return $dataTimeZ->format($format);
	}
}