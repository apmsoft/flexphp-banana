<?php
namespace Flex\Banana\Traits;

trait DelimitedStringTrait
{
    public function parseDelimited ( string $separator, string $value, array $default=[]) : ?array
    {
        $result = ($value) ?
                    ((strpos($value, $separator) !==false) ? explode($separator, $value) : [$value]) :
                    $default;
        return $result;
    }
}