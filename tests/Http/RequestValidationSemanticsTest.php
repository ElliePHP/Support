<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Tests\Http;

use ElliePHP\Components\Support\Http\Exception\ValidationException;
use ElliePHP\Components\Support\Http\Request;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class RequestValidationSemanticsTest extends TestCase
{
    public function testAbsentNullableFieldsAreNotReturned(): void
    {
        self::assertSame(['genre' => 'Soul'], $this->request(['genre' => 'Soul'])->validate([
            'url' => 'nullable|url',
            'genre' => 'sometimes|string',
        ]));
    }

    public function testExplicitNullIsPreservedForNullableFields(): void
    {
        self::assertSame(['artwork' => null], $this->request(['artwork' => null])->validate([
            'artwork' => 'sometimes|nullable|string',
        ]));
    }

    public function testSometimesValidatesAFieldWhenItIsPresent(): void
    {
        $this->expectException(ValidationException::class);
        $this->request(['history' => 'invalid'])->validate(['history' => 'sometimes|boolean']);
    }

    public function testStringRuleRejectsArrays(): void
    {
        $this->expectException(ValidationException::class);
        $this->request(['genre' => ['Jazz']])->validate(['genre' => 'required|string']);
    }

    public function testRequiredWithoutWorksWithAliasFields(): void
    {
        $this->expectException(ValidationException::class);
        $this->request([])->validate([
            'kind' => 'required_without:type|string',
            'type' => 'required_without:kind|string',
        ]);
    }

    public function testDefaultsAreRetainedWhenInputIsAbsent(): void
    {
        self::assertSame(['language' => 'en'], $this->request([])->validate([
            'language' => 'default:en|string',
        ]));
    }

    public function testNestedAbsentFieldsAreRemovedFromValidatedOutput(): void
    {
        self::assertSame(
            ['metadata' => ['genre' => 'Jazz']],
            $this->request(['metadata' => ['genre' => 'Jazz']])->validate([
                'metadata.genre' => 'sometimes|string',
                'metadata.language' => 'nullable|string',
            ]),
        );
    }

    public function testFailsUsesTheSameValidationPipeline(): void
    {
        self::assertTrue($this->request(['genre' => []])->fails(['genre' => 'string']));
        self::assertFalse($this->request(['genre' => 'Jazz'])->fails(['genre' => 'string']));
    }

    private function request(array $data): Request
    {
        return new Request((new ServerRequest('POST', '/'))->withParsedBody($data));
    }
}
