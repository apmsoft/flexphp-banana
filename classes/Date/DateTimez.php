<?php
namespace Flex\Banana\Classes\Date;

use \DateTime;
use \DateTimeZone;
use \DateInterval;

class DateTimez extends DateTime
{
	public const __version = '1.2';
	public DateTimeZone $dateTimeZone;
	public string $timezone;
	public array $location = [];
	public array $abbreviations = [];

	# time() || now || today || yesterday , Asia/Seoul
	public function __construct(string|int $times="now", string $timezone='')
	{
		# timezone
		if(!$timezone && function_exists('date_default_timezone_get')){
			$timezone = date_default_timezone_get();
		}
		$_timezone = $timezone ?? 'Asia/Seoul';
		$this->dateTimeZone = new DateTimeZone($_timezone);
		$this->timezone     = $this->dateTimeZone->getName();
		if(is_array($this->dateTimeZone->getLocation())){
			$this->location = $this->dateTimeZone->getLocation();
		}
		$this->filterAbbreviations(DateTimeZone::listAbbreviations());

		# datetime
		parent::__construct($this->chkTimestamp($times), $this->dateTimeZone);
	}

	private function filterAbbreviations(array $args) : void 
	{
		foreach($args as $abbr => $reviations){
			foreach($reviations as $rv){
				if($rv['timezone_id']){
					if($this->timezone == $rv['timezone_id']){
						$this->location['abbr']   = strtoupper($abbr);
						$this->location['offset'] = $rv['offset'];
						$this->location['utc']    = ($rv['offset'] != 0) ? $rv['offset'] / 3600 : 0;
						$this->location['dst']    = $rv['dst'];
						break;
					}
				}
			}
		}
	}

	public function chkTimestamp(string|int $times) : string 
	{
		# datetime
		$_times = $times;
		if(is_int($times)){
			$_times = '@'.$times;
		}
	return $_times;
	}

	# modify, add, sub 기능
	public function formatter(string $formatter) : DateTimez
    {
		if(strpos($formatter,'-P') !==false){
			parent::sub(new DateInterval( str_replace('-','',$formatter) ));
		}else if(substr($formatter,0,1) == 'P'){
			parent::add(new DateInterval($formatter));
		}else if(substr($formatter,0,2) == '+P'){
			parent::add(new DateInterval(str_replace('+','',$formatter)));
		}else{
			parent::modify($formatter);
		}
	return $this;
	}

	# 날짜를 배열로 / 오늘이 무슨요일인지 / 1년중 몇째날쨰인지 포함
	public function parseDate(string $formatter = 'Y-m-d H:i:s') : array 
	{
		$result = [];
		$localtimes = localtime(parent::format('U'),true);
		$result = date_parse(parent::format($formatter));
		$result['wday'] = $localtimes['tm_wday']; # 오늘이 주의 무슨요일에 해당하는지 0~6
		$result['yday'] = $localtimes['tm_yday']; # 오늘이 1년중 몇번째 날인지 체크 100/365
		unset($result['warning_count'],$result['warnings'],$result['error_count'],$result['errors']);

	return $result;
	}

	# 일몰/일출 정보
	public function sunInfo() : array 
	{
		$result = [
			'timezone'     => $this->timezone,
			'country_code' => $this->location['country_code'] ?? '',
			'today'        => parent::format('Y-m-d')
		];
		if(isset($this->location['latitude']) && $this->location['latitude']){
			$sun_info = date_sun_info(parent::format('U'), $this->location['latitude'], $this->location['longitude']);
			if(is_array($sun_info)){
				foreach ($sun_info as $fieldname => $val) {
					$result[$fieldname] = match ($val) {
						true    => 'always',
						false   => 'never',
						default => date_create("@".$val)->setTimeZone($this->dateTimeZone)->format( 'H:i:s' )
					};
				}
			}
		}
	return $result;
	}
}