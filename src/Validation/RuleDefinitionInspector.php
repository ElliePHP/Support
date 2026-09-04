<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Validation;

use Rakit\Validation\Rules\Defaults;

final readonly class RuleDefinitionInspector
{
    /** @return array{mixed, bool, bool} */
    public function inspect(mixed $definition): array
    {
        if (is_string($definition)) {
            $rules = explode('|', $definition);
            return [
                implode('|', $this->withoutSometimes($rules)),
                in_array('sometimes', $rules, strict: true),
                array_any($rules, $this->isDefaultRule(...)),
            ];
        }

        if (is_array($definition)) {
            return [
                $this->withoutSometimes($definition),
                in_array('sometimes', $definition, strict: true),
                array_any($definition, $this->isDefaultRule(...)),
            ];
        }

        return [$definition, false, $definition instanceof Defaults];
    }

    public function isAbsentOptionalString(mixed $definition, bool $present, bool $hasDefault): bool
    {
        return !$present
            && $this->hasDirective($definition, 'string')
            && !$this->hasRequiredDirective($definition)
            && !$hasDefault;
    }

    private function withoutSometimes(array $rules): array
    {
        return array_values(array_filter($rules, static fn(mixed $rule): bool => $rule !== 'sometimes'));
    }

    private function isDefaultRule(mixed $rule): bool
    {
        return $rule instanceof Defaults
            || (is_string($rule) && (
                str_starts_with($rule, 'default:') || str_starts_with($rule, 'defaults:')
            ));
    }

    private function hasDirective(mixed $definition, string $directive): bool
    {
        return array_any(
            $this->ruleList($definition),
            static fn(mixed $rule): bool => is_string($rule)
                && ($rule === $directive || str_starts_with($rule, $directive . ':')),
        );
    }

    private function hasRequiredDirective(mixed $definition): bool
    {
        return array_any(
            $this->ruleList($definition),
            static fn(mixed $rule): bool => is_string($rule) && str_starts_with($rule, 'required'),
        );
    }

    private function ruleList(mixed $definition): array
    {
        if (is_string($definition)) {
            return explode('|', $definition);
        }
        return is_array($definition) ? $definition : [];
    }
}
