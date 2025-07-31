<?php
namespace Flex\Banana\Traits;

# javascript editjs 이미지 내용 찾아 압축하기
# implements ImageCompressorInterface
# @ ImageCompressorBase64Trait : requied
trait ImageCompressorEditjsTrait
{
	// base64 이미지만 찾아 압축하고 리사이징하기
	public function compressDescriptionBase64Image(array $descriptions, int $width, int $height): array 
	{
		if (isset($descriptions['blocks']) && is_array($descriptions['blocks'])) {
			foreach ($descriptions['blocks'] as $idx => $content) {
				if (
					isset($content['type'], $content['data']['url']) &&
					$content['type'] === 'image' &&
					$this->isValidBase64Image($content['data']['url'])
				) {
					$original = $content['data']['url'];
					$resized = $this->resizeBase64Image($original, $width, $height);
					$descriptions['blocks'][$idx]['data']['url'] = $resized;
				}
			}
		}

		return $descriptions;
	}

	// 이미지만 찾아서 배열로 리턴
	public function getFindBase64Images(array $descriptions): array 
	{
		$images = [];

		if (isset($descriptions['blocks']) && is_array($descriptions['blocks'])) {
			foreach ($descriptions['blocks'] as $content) {
				if (
					isset($content['type'], $content['data']['url']) &&
					$content['type'] === 'image' &&
					$this->isValidBase64Image($content['data']['url'])
				) {
					$images[] = $content['data']['url'];
				}
			}
		}

		return $images;
	}

	protected function isValidBase64Image(string $base64): bool
	{
		return str_starts_with($base64, 'data:image/') && str_contains($base64, ';base64,');
	}

	# @ ImageCompressorBase64Trait : requied
	abstract protected function resizeBase64Image(string $base64_image, int $width, int $height) : string;
}