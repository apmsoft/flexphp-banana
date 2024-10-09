<?php
namespace Flex\Banana\Interface;

use Flex\Banana\Class\Image\ImageGDS;

interface ImageCompressorInterface
{
    public function getImageGDS(): ImageGDS;
}
?>