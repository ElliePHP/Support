<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Http;

use DateTimeInterface;
use ElliePHP\Components\Support\Util\Json;
use Exception;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class Response
{
    // Content Type Constants
    private const string CONTENT_TYPE_JSON = 'application/json';
    private const string CONTENT_TYPE_HTML = 'text/html; charset=utf-8';
    private const string CONTENT_TYPE_TEXT = 'text/plain; charset=utf-8';
    private const string CONTENT_TYPE_XML = 'application/xml; charset=utf-8';
    private const string CONTENT_TYPE_JAVASCRIPT = 'text/javascript';
    private const string CONTENT_TYPE_OCTET_STREAM = 'application/octet-stream';

    // HTTP Status Code Constants
    private const int HTTP_OK = 200;
    private const int HTTP_CREATED = 201;
    private const int HTTP_ACCEPTED = 202;
    private const int HTTP_NO_CONTENT = 204;
    private const int HTTP_MOVED_PERMANENTLY = 301;
    private const int  HTTP_FOUND = 302;
    private const int HTTP_SEE_OTHER = 303;
    private const int HTTP_BAD_REQUEST = 400;
    private const int HTTP_UNAUTHORIZED = 401;
    private const int HTTP_FORBIDDEN = 403;
    private const int HTTP_NOT_FOUND = 404;
    private const int HTTP_METHOD_NOT_ALLOWED = 405;
    private const int HTTP_CONFLICT = 409;
    private const int HTTP_UNPROCESSABLE_ENTITY = 422;
    private const int HTTP_TOO_MANY_REQUESTS = 429;
    private const int HTTP_INTERNAL_SERVER_ERROR = 500;
    private const int HTTP_SERVICE_UNAVAILABLE = 503;

    private Psr17Factory $factory;

    public function __construct(
        private readonly ?ResponseInterface $response = null
    )
    {
        $this->factory = new Psr17Factory();
    }

    /**
     * Create a new response.
     *
     * @param mixed $content Content (string, array, object).
     * @param int $status HTTP status code.
     * @param array $headers Headers.
     *
     * @return ResponseInterface
     */
    public function make(
        mixed $content = '',
        int   $status = self::HTTP_OK,
        array $headers = []
    ): ResponseInterface
    {
        // Auto-detect content type
        if (is_array($content) || is_object($content)) {
            return $this->json($content, $status, $headers);
        }

        $response = $this->factory->createResponse($status);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if ($content !== null && $content !== '') {
            $stream = $this->factory->createStream((string)$content);
            $response = $response->withBody($stream);
        }

        return $response;
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data Data to encode.
     * @param int $status HTTP status code.
     * @param array<string, string|string[]> $headers Additional headers.
     * @param int $flags JSON encoding flags.
     *
     * @return ResponseInterface
     * @throws RuntimeException If JSON encoding fails.
     */
    public function json(
        mixed $data,
        int   $status = self::HTTP_OK,
        array $headers = [],
        int   $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    ): ResponseInterface
    {
        try {
            $json = Json::safeEncode($data, $flags);
            if ($json === false) {
                throw new RuntimeException('Failed to encode JSON');
            }
        } catch (Exception $e) {
            throw new RuntimeException('Failed to encode JSON: ' . $e->getMessage(), 0, $e);
        }

        $response = $this->factory->createResponse($status);
        $response = $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($json);
        return $response->withBody($stream);
    }

    /**
     * Create a JSONP response.
     *
     * @param string $callback Callback function name.
     * @param mixed $data Data to encode.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     * @throws RuntimeException If JSON encoding fails.
     */
    public function jsonp(
        string $callback,
        mixed  $data,
        int    $status = self::HTTP_OK,
        array  $headers = []
    ): ResponseInterface
    {
        // Sanitize callback name
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $callback)) {
            throw new InvalidArgumentException('Invalid JSONP callback name');
        }

        $json = Json::safeEncode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $content = sprintf('/**/ typeof %s === \'function\' && %s(%s);', $callback, $callback, $json);

        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => self::CONTENT_TYPE_JAVASCRIPT], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($content);
        return $response->withBody($stream);
    }

    /**
     * Create an HTML response.
     *
     * @param string $html HTML content.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function html(
        string $html,
        int    $status = self::HTTP_OK,
        array  $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => self::CONTENT_TYPE_HTML], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($html);
        return $response->withBody($stream);
    }

    /**
     * Create a plain text response.
     *
     * @param string $text Text content.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function text(
        string $text,
        int    $status = self::HTTP_OK,
        array  $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => self::CONTENT_TYPE_TEXT], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($text);
        return $response->withBody($stream);
    }

    /**
     * Create an XML response.
     *
     * @param string $xml XML content.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function xml(
        string $xml,
        int    $status = self::HTTP_OK,
        array  $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => self::CONTENT_TYPE_XML], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($xml);
        return $response->withBody($stream);
    }

    /**
     * Create a redirect response.
     *
     * @param string $url Redirect URL.
     * @param int $status HTTP status code (301, 302, 303, 307, 308).
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirect(
        string $url,
        int    $status = self::HTTP_FOUND,
        array  $headers = []
    ): ResponseInterface
    {
        $this->validateRedirectStatus($status);

        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Location' => $url], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Create a permanent redirect (301).
     *
     * @param string $url Redirect URL.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirectPermanent(string $url, array $headers = []): ResponseInterface
    {
        return $this->redirect($url, self::HTTP_MOVED_PERMANENTLY, $headers);
    }

    /**
     * Create a temporary redirect (302).
     *
     * @param string $url Redirect URL.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirectTemporary(string $url, array $headers = []): ResponseInterface
    {
        return $this->redirect($url, self::HTTP_FOUND, $headers);
    }

    /**
     * Create a "see other" redirect (303).
     *
     * @param string $url Redirect URL.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirectSeeOther(string $url, array $headers = []): ResponseInterface
    {
        return $this->redirect($url, self::HTTP_SEE_OTHER, $headers);
    }

    /**
     * Create a redirect to previous URL.
     *
     * @param string|null $fallback Fallback URL.
     * @param int $status Status code.
     * @param array $allowedHosts Allowed hosts for redirect (empty = current host only).
     *
     * @return ResponseInterface
     */
    public function back(
        ?string $fallback = '/',
        int     $status = self::HTTP_FOUND,
        array   $allowedHosts = []
    ): ResponseInterface
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;

        // Validate referer URL
        if ($referer !== $fallback && !$this->isValidRedirectUrl($referer, $allowedHosts)) {
            $referer = $fallback;
        }

        return $this->redirect($referer, $status);
    }

    /**
     * Create an empty response.
     *
     * @param int $status HTTP status code.
     *
     * @return ResponseInterface
     */
    public function noContent(int $status = self::HTTP_NO_CONTENT): ResponseInterface
    {
        return $this->factory->createResponse($status);
    }

    /**
     * Alias for noContent().
     *
     * @param int $status HTTP status code.
     *
     * @return ResponseInterface
     */
    public function empty(int $status = self::HTTP_NO_CONTENT): ResponseInterface
    {
        return $this->noContent($status);
    }

    /**
     * Create a download response.
     *
     * @param string $content File content.
     * @param string $filename Download filename.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function download(
        string $content,
        string $filename,
        array  $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse(self::HTTP_OK);

        // Sanitize filename for Content-Disposition
        $sanitizedFilename = $this->sanitizeFilename($filename);

        foreach (array_merge([
            'Content-Type' => self::CONTENT_TYPE_OCTET_STREAM,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $sanitizedFilename),
            'Content-Length' => (string)strlen($content),
        ], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($content);
        return $response->withBody($stream);
    }

    /**
     * Create a file download response.
     *
     * @param string $path File path.
     * @param string|null $filename Download filename.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     * @throws RuntimeException If a file doesn't exist or can't be read.
     */
    public function file(
        string  $path,
        ?string $filename = null,
        array   $headers = []
    ): ResponseInterface
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        if (!is_readable($path)) {
            throw new RuntimeException("File not readable: $path");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read file: $path");
        }

        $filename = $filename ?? basename($path);

        return $this->download($content, $filename, $headers);
    }

    /**
     * Create a streamed download response.
     *
     * @param string $path File path.
     * @param string|null $filename Download filename.
     * @param array $headers Additional headers.
     * @param bool $deleteAfter Delete file after download.
     *
     * @return ResponseInterface
     * @throws RuntimeException If file doesn't exist or can't be read.
     */
    public function streamDownload(
        string  $path,
        ?string $filename = null,
        array   $headers = [],
        bool    $deleteAfter = false
    ): ResponseInterface
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        if (!is_readable($path)) {
            throw new RuntimeException("File not readable: $path");
        }

        $filename = $filename ?? basename($path);
        $resource = fopen($path, 'rb');

        if ($resource === false) {
            throw new RuntimeException("Failed to open file: $path");
        }

        $fileSize = filesize($path);
        if ($fileSize === false) {
            throw new RuntimeException("Failed to get file size: $path");
        }

        $response = $this->factory->createResponse(self::HTTP_OK);

        // Sanitize filename for Content-Disposition
        $sanitizedFilename = $this->sanitizeFilename($filename);

        $mimeType = mime_content_type($path) ?: self::CONTENT_TYPE_OCTET_STREAM;

        $response = $response->withHeader('Content-Type', $mimeType);
        $response = $response->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $sanitizedFilename));
        $response = $response->withHeader('Content-Length', (string)$fileSize);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStreamFromResource($resource);
        $response = $response->withBody($stream);

        if ($deleteAfter) {
            register_shutdown_function(static function () use ($path): void {
                @unlink($path);
            });
        }

        return $response;
    }

    /**
     * Create a 200 OK response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function ok(mixed $content = '', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_OK, $headers);
    }

    /**
     * Create a 201 Created response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function created(mixed $content = '', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_CREATED, $headers);
    }

    /**
     * Create a 202 Accepted response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function accepted(mixed $content = '', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_ACCEPTED, $headers);
    }

    /**
     * Create a 400 Bad Request response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function badRequest(mixed $content = 'Bad Request', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_BAD_REQUEST, $headers);
    }

    /**
     * Create a 401 Unauthorized response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function unauthorized(mixed $content = 'Unauthorized', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_UNAUTHORIZED, $headers);
    }

    /**
     * Create a 403 Forbidden response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function forbidden(mixed $content = 'Forbidden', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_FORBIDDEN, $headers);
    }

    /**
     * Create a 404 Not Found response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function notFound(mixed $content = 'Not Found', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_NOT_FOUND, $headers);
    }

    /**
     * Create a 405 Method Not Allowed response.
     *
     * @param array $allowed Allowed methods.
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function methodNotAllowed(
        array $allowed = [],
        mixed $content = 'Method Not Allowed',
        array $headers = []
    ): ResponseInterface
    {
        if (!empty($allowed)) {
            $headers['Allow'] = implode(', ', $allowed);
        }

        return $this->make($content, self::HTTP_METHOD_NOT_ALLOWED, $headers);
    }

    /**
     * Create a 409-Conflict response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function conflict(mixed $content = 'Conflict', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_CONFLICT, $headers);
    }

    /**
     * Create a 422 Unprocessable Entity response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function unprocessable(mixed $content = 'Unprocessable Entity', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_UNPROCESSABLE_ENTITY, $headers);
    }

    /**
     * Create a 429 Too Many Requests response.
     *
     * @param int|null $retryAfter Retry after seconds.
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function tooManyRequests(
        ?int  $retryAfter = null,
        mixed $content = 'Too Many Requests',
        array $headers = []
    ): ResponseInterface
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string)$retryAfter;
        }

        return $this->make($content, self::HTTP_TOO_MANY_REQUESTS, $headers);
    }

    /**
     * Create a 500 Internal Server Error response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function serverError(mixed $content = 'Internal Server Error', array $headers = []): ResponseInterface
    {
        return $this->make($content, self::HTTP_INTERNAL_SERVER_ERROR, $headers);
    }

    /**
     * Create a 503 Service Unavailable response.
     *
     * @param int|null $retryAfter Retry after seconds.
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function serviceUnavailable(
        ?int  $retryAfter = null,
        mixed $content = 'Service Unavailable',
        array $headers = []
    ): ResponseInterface
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string)$retryAfter;
        }

        return $this->make($content, self::HTTP_SERVICE_UNAVAILABLE, $headers);
    }

    /**
     * Get the underlying PSR-7 response.
     *
     * @return ResponseInterface
     * @throws RuntimeException If no response is available.
     */
    public function psr(): ResponseInterface
    {
        if ($this->response === null) {
            throw new RuntimeException('No response available. Use creation methods first.');
        }

        return $this->response;
    }

    /**
     * Alias for psr().
     *
     * @return ResponseInterface
     */
    public function raw(): ResponseInterface
    {
        return $this->psr();
    }

    /**
     * Get response status code.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->psr()->getStatusCode();
    }

    /**
     * Alias for status().
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status();
    }

    /**
     * Check if the response is successful (2xx).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Alias for isSuccessful().
     *
     * @return bool
     */
    public function successful(): bool
    {
        return $this->isSuccessful();
    }

    /**
     * Check if response is OK (200).
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->status() === self::HTTP_OK;
    }

    /**
     * Check if response is a redirect (3xx).
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Check if response is a client error (4xx).
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Check if response is a server error (5xx).
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->status() >= 500 && $this->status() < 600;
    }

    /**
     * Check if response is forbidden (403).
     *
     * @return bool
     */
    public function isForbidden(): bool
    {
        return $this->status() === self::HTTP_FORBIDDEN;
    }

    /**
     * Check if response is not found (404).
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->status() === self::HTTP_NOT_FOUND;
    }

    /**
     * Get response body as string.
     *
     * @return string
     */
    public function body(): string
    {
        return (string)$this->psr()->getBody();
    }

    /**
     * Alias for body().
     *
     * @return string
     */
    public function content(): string
    {
        return $this->body();
    }

    /**
     * Get response body as decoded JSON array.
     *
     * @param bool $associative Return associative array instead of objects.
     *
     * @return mixed
     * @throws RuntimeException If JSON decoding fails.
     */
    public function toArray(bool $associative = true): mixed
    {
        return Json::safeDecode($this->body(), $associative);
    }

    /**
     * Get response body as JSON string (re-encodes for pretty printing).
     *
     * @param int $flags JSON encoding flags.
     *
     * @return string
     * @throws RuntimeException If encoding fails.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return Json::safeEncode($this->toArray(), $flags | JSON_THROW_ON_ERROR);

    }

    /**
     * Get response headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->psr()->getHeaders();
    }

    /**
     * Get a header value.
     *
     * @param string $name
     * @param string|null $default
     *
     * @return string|null
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $values = $this->psr()->getHeader($name);
        return $values[0] ?? $default;
    }

    /**
     * Check if response has header.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return $this->psr()->hasHeader($name);
    }

    /**
     * Add a header to response.
     *
     * @param string $name Header name.
     * @param string|array $value Header value.
     *
     * @return ResponseInterface
     */
    public function withHeader(string $name, string|array $value): ResponseInterface
    {
        return $this->psr()->withHeader($name, $value);
    }

    /**
     * Alias for withHeader().
     *
     * @param string $name
     * @param string|array $value
     *
     * @return ResponseInterface
     */
    public function header(string $name, string|array $value): ResponseInterface
    {
        return $this->withHeader($name, $value);
    }

    /**
     * Add multiple headers to response.
     *
     * @param array $headers Headers array.
     *
     * @return ResponseInterface
     */
    public function withHeaders(array $headers): ResponseInterface
    {
        $response = $this->psr();
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }

    /**
     * Set response status.
     *
     * @param int $status Status code.
     * @param string $reason Reason phrase.
     *
     * @return ResponseInterface
     */
    public function withStatus(int $status, string $reason = ''): ResponseInterface
    {
        return $this->psr()->withStatus($status, $reason);
    }

    /**
     * Alias for withStatus().
     *
     * @param int $status
     * @param string $reason
     *
     * @return ResponseInterface
     */
    public function setStatusCode(int $status, string $reason = ''): ResponseInterface
    {
        return $this->withStatus($status, $reason);
    }

    /**
     * Set response body.
     *
     * @param string $body Body content.
     *
     * @return ResponseInterface
     */
    public function withBody(string $body): ResponseInterface
    {
        $stream = $this->factory->createStream($body);
        return $this->psr()->withBody($stream);
    }

    /**
     * Alias for withBody().
     *
     * @param string $content
     *
     * @return ResponseInterface
     */
    public function setContent(string $content): ResponseInterface
    {
        return $this->withBody($content);
    }

    /**
     * Set cookie header.
     *
     * @param string $name Cookie name.
     * @param string $value Cookie value.
     * @param int $expires Expiration Unix timestamp.
     * @param string $path Cookie path.
     * @param string $domain Cookie domain.
     * @param bool $secure Secure flag.
     * @param bool $httpOnly HTTP-only flag.
     * @param string $sameSite SameSite attribute.
     *
     * @return ResponseInterface
     */
    public function withCookie(
        string $name,
        string $value,
        int    $expires = 0,
        string $path = '/',
        string $domain = '',
        bool   $secure = false,
        bool   $httpOnly = true,
        string $sameSite = 'Lax'
    ): ResponseInterface
    {
        $cookie = urlencode($name) . '=' . urlencode($value);

        if ($expires > 0) {
            $cookie .= '; Expires=' . gmdate('D, d M Y H:i:s T', $expires);
            $cookie .= '; Max-Age=' . ($expires - time());
        }

        if ($path) {
            $cookie .= '; Path=' . $path;
        }

        if ($domain) {
            $cookie .= '; Domain=' . $domain;
        }

        if ($secure) {
            $cookie .= '; Secure';
        }

        if ($httpOnly) {
            $cookie .= '; HttpOnly';
        }

        if (in_array($sameSite, ['Strict', 'Lax', 'None'], true)) {
            $cookie .= '; SameSite=' . $sameSite;
        }

        return $this->psr()->withAddedHeader('Set-Cookie', $cookie);
    }

    /**
     * Set cookie with expiration in minutes (convenience method).
     *
     * @param string $name
     * @param string $value
     * @param int $minutes Expiration in minutes.
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     *
     * @return ResponseInterface
     */
    public function cookie(
        string $name,
        string $value,
        int    $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool   $secure = false,
        bool   $httpOnly = true,
        string $sameSite = 'Lax'
    ): ResponseInterface
    {
        $expires = $minutes > 0 ? time() + ($minutes * 60) : 0;
        return $this->withCookie($name, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * Delete a cookie.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     *
     * @return ResponseInterface
     */
    public function withoutCookie(
        string $name,
        string $path = '/',
        string $domain = '',
        bool   $secure = false,
        bool   $httpOnly = true,
        string $sameSite = 'Lax'
    ): ResponseInterface
    {
        return $this->withCookie($name, '', time() - 3600, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * Set content type header.
     *
     * @param string $contentType
     *
     * @return ResponseInterface
     */
    public function contentType(string $contentType): ResponseInterface
    {
        return $this->withHeader('Content-Type', $contentType);
    }

    /**
     * Set cache control header.
     *
     * @param string $value
     *
     * @return ResponseInterface
     */
    public function cacheControl(string $value): ResponseInterface
    {
        return $this->withHeader('Cache-Control', $value);
    }

    /**
     * Set response to not be cached.
     *
     * @return ResponseInterface
     */
    public function noCache(): ResponseInterface
    {
        return $this->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Set ETag header.
     *
     * @param string $etag
     *
     * @return ResponseInterface
     */
    public function etag(string $etag): ResponseInterface
    {
        return $this->withHeader('ETag', $etag);
    }

    /**
     * Set Last-Modified header.
     *
     * @param int|DateTimeInterface $time
     *
     * @return ResponseInterface
     */
    public function lastModified(int|DateTimeInterface $time): ResponseInterface
    {
        if ($time instanceof DateTimeInterface) {
            $time = $time->getTimestamp();
        }

        return $this->withHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $time));
    }

    /**
     * Send response to client.
     *
     * @return void
     */
    public function send(): void
    {
        $this->emitStatusLine();
        $this->emitHeaders();
        $this->emitBody();
    }

    /**
     * Send response and exit.
     *
     * @return never
     */
    public function sendAndExit(): never
    {
        $this->send();
        exit;
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Validate redirect status code.
     *
     * @param int $status
     *
     * @return void
     * @throws InvalidArgumentException If status is not a valid redirect code.
     */
    private function validateRedirectStatus(int $status): void
    {
        $validRedirectCodes = [301, 302, 303, 307, 308];

        if (!in_array($status, $validRedirectCodes, true)) {
            throw new InvalidArgumentException(
                "Invalid redirect status code: $status. Must be one of: " . implode(', ', $validRedirectCodes)
            );
        }
    }

    /**
     * Validate if URL is safe for redirect.
     *
     * @param string $url
     * @param array $allowedHosts
     *
     * @return bool
     */
    private function isValidRedirectUrl(string $url, array $allowedHosts): bool
    {
        // Parse URL
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            // Relative URL or malformed - allow it
            return true;
        }

        $urlHost = $parsedUrl['host'];
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';

        // If no allowed hosts specified, only allow the current host
        if (empty($allowedHosts)) {
            return $urlHost === $currentHost;
        }

        // Check if host is in an allowed list
        return in_array($urlHost, $allowedHosts, true);
    }

    /**
     * Sanitize filename for Content-Disposition header.
     *
     * @param string $filename
     *
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);

        // Remove any characters that could be problematic in headers
        return preg_replace('/[^\w\-.]/', '_', $filename);
    }

    /**
     * Emit status line.
     *
     * @return void
     */
    private function emitStatusLine(): void
    {
        $response = $this->psr();

        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        header($statusLine, true, $response->getStatusCode());
    }

    /**
     * Emit headers.
     *
     * @return void
     */
    private function emitHeaders(): void
    {
        foreach ($this->psr()->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
    }

    /**
     * Emit body.
     *
     * @return void
     */
    private function emitBody(): void
    {
        echo $this->psr()->getBody();
    }
}