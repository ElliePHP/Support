<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Tests\Http;

use ElliePHP\Components\Support\Http\Request;
use ElliePHP\Components\Support\Http\Exception\ValidationException;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

final class RequestJsonValidationTest extends TestCase
{
    public function testItValidatesJsonRequestBodies(): void
    {
        $request = $this->jsonRequest(['kind' => 'stream', 'url' => 'https://example.com/radio']);

        self::assertSame([
            'kind' => 'stream',
            'url' => 'https://example.com/radio',
        ], $request->validate([
            'kind' => 'required|string',
            'url' => 'required|url',
        ]));
    }

    public function testJsonBodyTakesPrecedenceOverQueryInput(): void
    {
        $psrRequest = (new ServerRequest('POST', '/metadata?kind=query'))
            ->withQueryParams(['kind' => 'query'])
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create('{"kind":"json"}'));

        self::assertSame(['kind' => 'json'], (new Request($psrRequest))->validate([
            'kind' => 'required|string',
        ]));
    }

    public function testMalformedJsonProducesAValidationError(): void
    {
        $request = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create('{invalid'));

        $this->expectException(ValidationException::class);
        (new Request($request))->validate(['kind' => 'sometimes|string']);
    }

    private function jsonRequest(array $data): Request
    {
        $request = (new ServerRequest('POST', '/'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::create(json_encode($data, JSON_THROW_ON_ERROR)));

        return new Request($request);
    }
}
