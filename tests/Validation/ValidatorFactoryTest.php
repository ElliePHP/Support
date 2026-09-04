<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Tests\Validation;

use ElliePHP\Components\Support\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Rule;

final class ValidatorFactoryTest extends TestCase
{
    public function testApplicationsCanExtendTheValidatorWithoutChangingRequest(): void
    {
        $factory = (new ValidatorFactory())->extend('slug', new class extends Rule {
            protected $message = 'The :attribute must be a slug';

            public function check($value): bool
            {
                return is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
            }
        });

        $validation = $factory->create()->make(['slug' => 'metadata-api'], ['slug' => 'required|slug']);
        $validation->validate();

        self::assertFalse($validation->fails());
    }
}
