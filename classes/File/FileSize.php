<?php
namespace Flex\Banana\Classes\File;

use \Exception;

# 파일 용량을 알아보기 쉽도록 변환
class FileSize
{
	public const __version = '1.0';
	protected string $filename;
	protected $filesize_bytes = 0;
	private array $convert_type = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');

	#@ void
	# 파일전체 경로
	public function __construct(string $filenamez=''){
		if($filenamez){
			if(!$filenamez) throw new Exception( 'e_filenotfound' );
			if(!file_exists($filenamez)) throw new Exception( 'e_filenotfound');

			$this->filename = $filenamez;
			$this->filesize_bytes = filesize($this->filename);
		}
	}

	# 파일사이즈 등록
	public function setBytes(int $bytes) : FileSize{
		if(!empty($bytes))
			$this->filesize_bytes = $bytes;

	return $this;
	}

	#@ 바이트 단위로 
	private function bytes() : int{
		return $this->filesize_bytes;
	}

	#@ 문자 단위로
	private function size() : string{
		$result = "0";
		if(!empty($this->filesize_bytes)){
			$e = floor(log($this->filesize_bytes)/log(1024));
			$result = sprintf('%.2f %s', ($this->filesize_bytes/pow(1024, floor($e))), $this->convert_type[$e]);
		}
	return $result;
	}

	public function __call(string $method, array $params = []) : mixed {
		$result = '';
		if(!method_exists($this, $method)){
			throw new Exception( 'e_not_found_method');
		}

		$result = match($method){
			'bytes'  => $this->bytes(),
			'size' => $this->size()
		};

	return $result;
	}
}