<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Validation;

final readonly class InputPath
{
    public function exists(array $data, string $path): bool
    {
        return $this->segmentsExist($data, explode('.', $path));
    }

    private function segmentsExist(mixed $value, array $segments): bool
    {
        if ($segments === []) {
            return true;
        }
        if (!is_array($value)) {
            return false;
        }

        $segment = array_shift($segments);
        if ($segment === '*') {
            return array_any($value, fn(mixed $item): bool => $this->segmentsExist($item, $segments));
        }

        return array_key_exists($segment, $value) && $this->segmentsExist($value[$segment], $segments);
    }
}
