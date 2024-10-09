<?php
namespace Flex\Banana\Interface;

use Flex\Banana\Image\ImageGDS;

interface ImageCompressorInterface
{
    public function getImageGDS(): ImageGDS;
}
?>