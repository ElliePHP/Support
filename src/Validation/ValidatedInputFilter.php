<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Validation;

final readonly class ValidatedInputFilter
{
    public function retain(array $validated, array $input, array $defaultPaths, string $prefix = ''): array
    {
        $result = [];

        foreach ($validated as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            $present = array_key_exists($key, $input);

            if (!$present && !$this->pathIsDefaulted($path, $defaultPaths)) {
                continue;
            }
            if ($present && is_array($value) && is_array($input[$key])) {
                $value = $this->retain($value, $input[$key], $defaultPaths, $path);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function pathIsDefaulted(string $path, array $defaultPaths): bool
    {
        return array_any(
            $defaultPaths,
            static fn(string $default): bool => $default === $path || str_starts_with($default, $path . '.'),
        );
    }
}
