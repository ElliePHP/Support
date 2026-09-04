<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Validation;

final readonly class RulePreprocessor
{
    public function __construct(
        private RuleDefinitionInspector $inspector = new RuleDefinitionInspector(),
        private InputPath $inputPath = new InputPath(),
    ) {
    }

    /** @return array{array, list<string>} */
    public function prepare(array $data, array $rules): array
    {
        $active = [];
        $defaults = [];

        foreach ($rules as $attribute => $definition) {
            [$definition, $sometimes, $hasDefault] = $this->inspector->inspect($definition);
            $present = $this->inputPath->exists($data, (string) $attribute);

            if ($sometimes && !$present) {
                continue;
            }
            if ($this->inspector->isAbsentOptionalString($definition, $present, $hasDefault)) {
                continue;
            }

            $active[$attribute] = $definition;
            if ($hasDefault) {
                $defaults[] = (string) $attribute;
            }
        }

        return [$active, $defaults];
    }

}
