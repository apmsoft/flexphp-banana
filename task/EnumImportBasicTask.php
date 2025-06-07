<?php
namespace Flex\Banana\Task;

use Flex\Banana\Classes\Log;

class EnumImportBasicTask
{
    public const __version = '0.2.0';
    private array $enumClassNames;

    public function __construct(array $enumClassNames)
    {
        $this->enumClassNames = $enumClassNames;
    }

    public function execute(): array
    {
        $result = [];
        // Log::d('** enumClassNames **',$this->enumClassNames);

        foreach ($this->enumClassNames as $enum) {
            Log::d('>> raw enum input', $enum);

            if (!is_string($enum)) {
                Log::w("Skipped non-string enum: " . json_encode($enum));
                continue;
            }

            if (empty($enum)) {
                Log::w("Skipped empty enum after unescape.");
                continue;
            }

            if (!class_exists($enum)) {
                Log::e("Enum class not found: " . $enum);
                continue;
            }

            $ref = new \ReflectionClass($enum);
            if ($ref->isEnum()) {
                $cases = $enum::cases();
                $short = $ref->getShortName();
                $result[$short] = $cases[0] ?? null;
            }
        }

        // Log::d('** enumClassNames :: result **',$result);

        return $result;
    }
}