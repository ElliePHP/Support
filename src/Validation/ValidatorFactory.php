<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Validation;

use ElliePHP\Components\Support\Validation\Rules\StringType;
use InvalidArgumentException;
use Rakit\Validation\Rule;
use Rakit\Validation\Validator;

final class ValidatorFactory
{
    /** @var array<string, Rule> */
    private array $rules;

    /** @param array<string, Rule> $rules */
    public function __construct(array $rules = [])
    {
        $this->rules = ['string' => new StringType(), ...$rules];
    }

    public function extend(string $name, Rule $rule): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Validation rule name cannot be empty');
        }

        $clone = clone $this;
        $clone->rules[$name] = $rule;
        return $clone;
    }

    public function create(): Validator
    {
        $validator = new Validator();
        foreach ($this->rules as $name => $rule) {
            $validator->addValidator($name, clone $rule);
        }

        return $validator;
    }
}
