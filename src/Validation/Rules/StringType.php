<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Validation\Rules;

use Rakit\Validation\Rule;

final class StringType extends Rule
{
    protected $message = 'The :attribute must be a string';
    protected $implicit = true;

    public function check($value): bool
    {
        return is_string($value);
    }
}
