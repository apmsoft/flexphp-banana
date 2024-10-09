<?php
namespace Flex\Components\DataProcessing;

use Flex\Banana\Image\ImageGDS;

interface ImageCompressorInterface
{
    public function getImageGDS(): ImageGDS;
}
?>