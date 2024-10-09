<?php
namespace Flex\Banana\Interfaces;

use Flex\Banana\Classes\Image\ImageGDS;

interface ImageCompressorInterface
{
    public function getImageGDS(): ImageGDS;
}
?>