<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Validation;

use ElliePHP\Components\Support\Http\Exception\ValidationException;

final readonly class RequestValidator
{
    public function __construct(
        private ValidatorFactory $factory = new ValidatorFactory(),
        private RulePreprocessor $rules = new RulePreprocessor(),
        private ValidatedInputFilter $filter = new ValidatedInputFilter(),
    ) {
    }

    public function validate(array $data, array $rules, array $messages = []): array
    {
        [$activeRules, $defaultPaths] = $this->rules->prepare($data, $rules);
        $validation = $this->factory->create()->make($data, $activeRules, $messages);
        $validation->validate();

        if ($validation->fails()) {
            throw new ValidationException($validation->errors());
        }

        return $this->filter->retain(
            $validation->getValidatedData(),
            $data,
            $defaultPaths,
        );
    }

}
