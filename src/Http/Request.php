<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Http;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use ElliePHP\Components\Support\Util\Json;
use ElliePHP\Components\Support\Util\Str;
use Exception;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class Request
{
    private static ?ServerRequestCreator $creator = null;

    /**
     * Trusted proxy IP addresses.
     */
    private array $trustedProxies = [];

    public function __construct(
        private readonly ServerRequestInterface $request
    ) {
    }

    /**
     * Create a server request from globals.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        if (self::$creator === null) {
            $factory = new Psr17Factory();
            self::$creator = new ServerRequestCreator(
                $factory,
                $factory,
                $factory,
                $factory
            );
        }

        return new self(self::$creator->fromGlobals());
    }

    /**
     * Create a new request.
     *
     * @param string $method HTTP method.
     * @param string $uri URI.
     * @param array<string, string|string[]> $headers Headers.
     * @param string|resource|null $body Body.
     * @param string $version Protocol version.
     *
     * @return self
     */
    public static function create(
        string $method,
        string $uri,
        array  $headers = [],
        mixed  $body = null,
        string $version = '1.1'
    ): self {
        $factory = new Psr17Factory();
        $request = $factory->createRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $stream = is_resource($body)
                ? $factory->createStreamFromResource($body)
                : $factory->createStream($body);
            $request = $request->withBody($stream);
        }

        return new self($request->withProtocolVersion($version));
    }

    /**
     * Set trusted proxy IP addresses.
     *
     * @param array<string> $proxies
     *
     * @return self
     */
    public function setTrustedProxies(array $proxies): self
    {
        $this->trustedProxies = $proxies;
        return $this;
    }

    /**
     * Get trusted proxies.
     *
     * @return array<string>
     */
    public function getTrustedProxies(): array
    {
        return $this->trustedProxies;
    }

    /**
     * Get the underlying PSR-7 request.
     *
     * @return ServerRequestInterface
     */
    public function psr(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Alias for psr().
     *
     * @return ServerRequestInterface
     */
    public function raw(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Get request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get request URI.
     *
     * @return string
     */
    public function uri(): string
    {
        return (string) $this->request->getUri();
    }

    /**
     * Alias for uri().
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri();
    }

    /**
     * Alias for uri().
     *
     * @return string
     */
    public function url(): string
    {
        return $this->uri();
    }

    /**
     * Get the full URL with query string.
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $uri = $this->request->getUri();
        $url = $this->urlWithoutQuery();
        $query = $uri->getQuery();

        if ($query !== '') {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * Get request path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->request->getUri()->getPath();
    }

    /**
     * Get the URL without query parameters.
     *
     * @return string
     */
    public function urlWithoutQuery(): string
    {
        $uri = $this->request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();
        $path = $uri->getPath();

        $url = $scheme . '://' . $host;

        if ($port && (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443))) {
            $url .= ':' . $port;
        }

        return $url . $path;
    }

    /**
     * Get URL segment by index (1-based).
     *
     * @param int $index Segment index (1-based).
     * @param string|null $default Default value.
     *
     * @return string|null
     */
    public function segment(int $index, ?string $default = null): ?string
    {
        $segments = array_values(array_filter(explode('/', $this->path())));
        return $segments[$index - 1] ?? $default;
    }

    /**
     * Get all URL segments.
     *
     * @return array<string>
     */
    public function segments(): array
    {
        return array_values(array_filter(explode('/', $this->path())));
    }

    /**
     * Check if current URL matches given pattern(s).
     *
     * @param string ...$patterns
     *
     * @return bool
     */
    public function is(string ...$patterns): bool
    {
        $path = $this->path();

        return array_any($patterns, static fn($pattern) => fnmatch($pattern, $path));

    }

    /**
     * Get a query parameter.
     *
     * @param string|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->request->getQueryParams();
        }

        return $this->request->getQueryParams()[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     *
     * @return array
     */
    public function allQuery(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * Get a POST parameter.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        $body = $this->request->getParsedBody();
        return is_array($body) ? ($body[$key] ?? $default) : $default;
    }

    /**
     * Get all POST parameters.
     *
     * @return array
     */
    public function allPost(): array
    {
        $body = $this->request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    /**
     * Get input from query or post.
     *
     * @param string|array|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function input(string|array|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->all();
        }

        if (is_array($key)) {
            return $this->only($key);
        }

        return $this->query($key) ?? $this->post($key, $default);
    }

    /**
     * Get all input data.
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->allQuery(), $this->allPost());
    }

    /**
     * Get only specified keys from input.
     *
     * @param array<string> $keys
     *
     * @return array
     */
    public function only(array $keys): array
    {
        $input = $this->all();
        return array_intersect_key($input, array_flip($keys));
    }

    /**
     * Get all input except specified keys.
     *
     * @param array<string> $keys
     *
     * @return array
     */
    public function except(array $keys): array
    {
        $input = $this->all();
        return array_diff_key($input, array_flip($keys));
    }

    /**
     * Check if input has a key.
     *
     * @param string|array<string> $key
     *
     * @return bool
     */
    public function has(string|array $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        $input = $this->all();

        return array_all($keys, static fn($k) => array_key_exists($k, $input));

    }

    /**
     * Check if input has any of the given keys.
     *
     * @param string|array<string> $keys
     *
     * @return bool
     */
    public function hasAny(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = $this->all();

        return array_any($keys, static fn($key) => array_key_exists($key, $input));

    }

    /**
     * Check if input key exists and is not empty.
     * Note: "0", 0, and false are considered filled.
     *
     * @param string|array<string> $key
     *
     * @return bool
     */
    public function filled(string|array $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $k) {
            if (!$this->has($k)) {
                return false;
            }

            $value = $this->input($k);

            // Only null and empty string are considered not filled
            if ($value === null || $value === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the given keys are filled.
     *
     * @param string|array<string> $keys
     *
     * @return bool
     */
    public function anyFilled(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return array_any($keys, fn($key) => $this->filled($key));

    }

    /**
     * Check if input is missing a key.
     *
     * @param string|array<string> $key
     *
     * @return bool
     */
    public function missing(string|array $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Get input when filled, otherwise return default.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function whenFilled(string $key, mixed $default = null): mixed
    {
        if ($this->filled($key)) {
            return $this->input($key);
        }

        return $default;
    }

    /**
     * Get input when has key, otherwise return default.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function whenHas(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->input($key);
        }

        return $default;
    }

    /**
     * Get parsed body.
     *
     * @return array|object|null
     */
    public function body(): array|null|object
    {
        return $this->request->getParsedBody();
    }

    /**
     * Get raw body as string.
     *
     * @return string
     */
    public function rawBody(): string
    {
        return (string) $this->request->getBody();
    }

    /**
     * Get input as boolean.
     *
     * @param string $key
     * @param bool $default
     *
     * @return bool
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }

        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $result ?? $default;
    }

    /**
     * Alias for bool().
     *
     * @param string $key
     * @param bool $default
     *
     * @return bool
     */
    public function boolean(string $key, bool $default = false): bool
    {
        return $this->bool($key, $default);
    }

    /**
     * Get input as integer.
     *
     * @param string $key
     * @param int $default
     *
     * @return int
     */
    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }

        $result = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        return $result ?? $default;
    }

    /**
     * Alias for int().
     *
     * @param string $key
     * @param int $default
     *
     * @return int
     */
    public function integer(string $key, int $default = 0): int
    {
        return $this->int($key, $default);
    }

    /**
     * Get input as float.
     *
     * @param string $key
     * @param float $default
     *
     * @return float
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }

        $result = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        return $result !== false && $result !== null ? $result : $default;
    }

    /**
     * Get input as string.
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key);
        return $value !== null ? (string) $value : $default;
    }

    /**
     * Get input as array.
     *
     * @param string $key
     * @param array $default
     *
     * @return array
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->input($key);
        return is_array($value) ? $value : $default;
    }

    /**
     * Get input as enum.
     *
     * @template T of BackedEnum
     * @param string $key
     * @param class-string<T> $enumClass
     * @param T|null $default
     *
     * @return T|null
     */
    public function enum(string $key, string $enumClass, ?BackedEnum $default = null): ?BackedEnum
    {
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }

        if (!enum_exists($enumClass)) {
            throw new InvalidArgumentException("Class {$enumClass} is not an enum");
        }

        return $enumClass::tryFrom($value) ?? $default;
    }

    /**
     * Get input as date.
     *
     * @param string $key
     * @param string|null $format
     * @param DateTimeZone|null $timezone
     *
     * @return DateTimeInterface|null
     */
    public function date(string $key, ?string $format = null, ?DateTimeZone $timezone = null): ?DateTimeInterface
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        if ($format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);
            return $date !== false ? $date : null;
        }

        try {
            return new DateTimeImmutable($value, $timezone);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Get body as JSON.
     *
     * @param bool $assoc
     *
     * @return mixed
     */
    public function json(bool $assoc = true): mixed
    {
        $body = $this->rawBody();

        if ($body === '') {
            return $assoc ? [] : null;
        }

        return Json::safeDecode($body, $assoc);
    }

    /**
     * Get a header value.
     *
     * @param string|null $name
     * @param string|null $default
     *
     * @return string|array|null
     */
    public function header(?string $name = null, ?string $default = null): string|array|null
    {
        if ($name === null) {
            return $this->headers();
        }

        $values = $this->request->getHeader($name);
        return $values[0] ?? $default;
    }

    /**
     * Check if request has header.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    /**
     * Get all headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * Get bearer token from header.
     *
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');

        if ($header && is_string($header) && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    /**
     * Get basic auth credentials.
     *
     * @return array{username: string, password: string}|null
     */
    public function basicAuth(): ?array
    {
        $header = $this->header('Authorization');

        if ($header && is_string($header) && str_starts_with($header, 'Basic ')) {
            $decoded = base64_decode(substr($header, 6), true);

            if ($decoded === false) {
                return null;
            }

            $parts = explode(':', $decoded, 2);

            if (count($parts) === 2) {
                return [
                    'username' => $parts[0],
                    'password' => $parts[1]
                ];
            }
        }

        return null;
    }

    /**
     * Check if request is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return is_string($contentType) && Str::contains($contentType, 'application/json');
    }

    /**
     * Check if request expects JSON.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        return $this->isJson() || $this->wantsJson();
    }

    /**
     * Check if request wants JSON response.
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        $acceptable = $this->header('Accept', '');
        return is_string($acceptable) && Str::contains($acceptable, 'application/json');
    }

    /**
     * Check if request accepts given content type(s).
     *
     * @param string|array<string> $contentTypes
     *
     * @return bool
     */
    public function accepts(string|array $contentTypes): bool
    {
        $acceptable = $this->header('Accept', '');

        if (!is_string($acceptable)) {
            return false;
        }

        $contentTypes = is_array($contentTypes) ? $contentTypes : func_get_args();

        return array_any($contentTypes, static fn($type) => Str::contains($acceptable, $type) || $acceptable === '*/*');

    }

    /**
     * Get the most acceptable content type from the given types.
     *
     * @param array<string> $contentTypes
     *
     * @return string|null
     */
    public function prefers(array $contentTypes): ?string
    {
        return array_find($contentTypes, fn($type) => $this->accepts($type));

    }

    /**
     * Check if request is AJAX.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Alias for isAjax().
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return $this->isAjax();
    }

    /**
     * Check if request method matches.
     *
     * @param string $method
     *
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strcasecmp($this->request->getMethod(), $method) === 0;
    }

    /**
     * Check if request is GET.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if request is POST.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if request is PUT.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Check if request is PATCH.
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Check if request is DELETE.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Check if request is HEAD.
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Check if request is OPTIONS.
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Check if request is secure (HTTPS).
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->request->getUri()->getScheme() === 'https';
    }

    /**
     * Get the scheme.
     *
     * @return string
     */
    public function scheme(): string
    {
        return $this->request->getUri()->getScheme();
    }

    /**
     * Get the host.
     *
     * @return string
     */
    public function host(): string
    {
        return $this->request->getUri()->getHost();
    }

    /**
     * Get HTTP host (host:port).
     *
     * @return string
     */
    public function httpHost(): string
    {
        $host = $this->host();
        $port = $this->port();

        if ($port && (($this->scheme() === 'http' && $port !== 80) ||
                ($this->scheme() === 'https' && $port !== 443))) {
            return $host . ':' . $port;
        }

        return $host;
    }

    /**
     * Get the port.
     *
     * @return int|null
     */
    public function port(): ?int
    {
        return $this->request->getUri()->getPort();
    }

    /**
     * Get uploaded files.
     *
     * @return array
     */
    public function files(): array
    {
        return $this->request->getUploadedFiles();
    }

    /**
     * Alias for files().
     *
     * @return array
     */
    public function allFiles(): array
    {
        return $this->files();
    }

    /**
     * Get a single uploaded file.
     *
     * @param string $key
     *
     * @return UploadedFileInterface|null
     */
    public function file(string $key): ?UploadedFileInterface
    {
        $file = $this->request->getUploadedFiles()[$key] ?? null;
        return $file instanceof UploadedFileInterface ? $file : null;
    }

    /**
     * Check if request has file.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file !== null && $file->getError() === UPLOAD_ERR_OK;
    }

    /**
     * Get cookies.
     *
     * @return array
     */
    public function cookies(): array
    {
        return $this->request->getCookieParams();
    }

    /**
     * Get a cookie value.
     *
     * @param string|null $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function cookie(?string $name = null, mixed $default = null): mixed
    {
        if ($name === null) {
            return $this->cookies();
        }

        return $this->request->getCookieParams()[$name] ?? $default;
    }

    /**
     * Check if request has cookie.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasCookie(string $name): bool
    {
        return array_key_exists($name, $this->cookies());
    }

    /**
     * Get server parameters.
     *
     * @return array
     */
    public function server(): array
    {
        return $this->request->getServerParams();
    }

    /**
     * Get a server parameter.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function serverParam(string $key, mixed $default = null): mixed
    {
        return $this->request->getServerParams()[$key] ?? $default;
    }

    /**
     * Get client IP address (secured against spoofing).
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        $server = $this->request->getServerParams();
        $remoteAddr = $server['REMOTE_ADDR'] ?? null;

        // If no trusted proxies configured, only use REMOTE_ADDR
        if (empty($this->trustedProxies)) {
            return $remoteAddr;
        }

        // Only trust X-Forwarded-For if request is from trusted proxy
        if ($remoteAddr && in_array($remoteAddr, $this->trustedProxies, true)) {
            if (isset($server['HTTP_X_FORWARDED_FOR'])) {
                $ips = array_map('trim', explode(',', $server['HTTP_X_FORWARDED_FOR']));

                // Return the first IP that is not a trusted proxy
                foreach ($ips as $ip) {
                    if (!in_array($ip, $this->trustedProxies, true) && $this->isValidIp($ip)) {
                        return $ip;
                    }
                }
            }

            // Check other headers
            if (isset($server['HTTP_CLIENT_IP']) && $this->isValidIp($server['HTTP_CLIENT_IP'])) {
                return $server['HTTP_CLIENT_IP'];
            }
        }

        return $remoteAddr;
    }

    /**
     * Get all client IPs from X-Forwarded-For header.
     *
     * @return array<string>
     */
    public function ips(): array
    {
        $server = $this->request->getServerParams();

        // Only parse X-Forwarded-For if from trusted proxy
        if (isset($server['REMOTE_ADDR'], $server['HTTP_X_FORWARDED_FOR']) && !empty($this->trustedProxies) && in_array($server['REMOTE_ADDR'], $this->trustedProxies, true)) {

            $ips = array_map('trim', explode(',', $server['HTTP_X_FORWARDED_FOR']));
            return array_filter($ips, fn($ip) => $this->isValidIp($ip));
        }

        $ip = $this->ip();
        return $ip ? [$ip] : [];
    }

    /**
     * Get client IP from Cloudflare headers.
     * Use this when your application is behind Cloudflare.
     *
     * @return string|null
     */
    public function ipFromCloudflare(): ?string
    {
        $server = $this->request->getServerParams();

        // CF-Connecting-IP is the most reliable for Cloudflare
        if (isset($server['HTTP_CF_CONNECTING_IP'])) {
            $ip = trim($server['HTTP_CF_CONNECTING_IP']);

            if ($this->isValidIp($ip)) {
                return $ip;
            }
        }

        // Fallback to standard methods
        return $this->ip();
    }

    /**
     * Check if request is from Cloudflare.
     * Validates by checking if REMOTE_ADDR is in Cloudflare's IP ranges.
     *
     * @return bool
     */
    public function isFromCloudflare(): bool
    {
        $server = $this->request->getServerParams();

        // Check for CF headers
        if (!isset($server['HTTP_CF_RAY']) && !isset($server['HTTP_CF_CONNECTING_IP'])) {
            return false;
        }

        // Cloudflare IP ranges (IPv4 - subset for validation)
        // Full list at: https://www.cloudflare.com/ips-v4
        $cloudflareRanges = [
            '173.245.48.0/20',
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '141.101.64.0/18',
            '108.162.192.0/18',
            '190.93.240.0/20',
            '188.114.96.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '162.158.0.0/15',
            '104.16.0.0/13',
            '104.24.0.0/14',
            '172.64.0.0/13',
            '131.0.72.0/22',
        ];

        $remoteAddr = $server['REMOTE_ADDR'] ?? null;

        if (!$remoteAddr) {
            return false;
        }

        return $this->isFrom($cloudflareRanges);
    }

    /**
     * Get Cloudflare Ray ID for debugging.
     *
     * @return string|null
     */
    public function cloudflareRayId(): ?string
    {
        $rayId = $this->header('CF-Ray');
        return is_string($rayId) ? $rayId : null;
    }

    /**
     * Get Cloudflare visitor info.
     * Returns information about the visitor's connection through Cloudflare.
     *
     * @return array{ip: string|null, country: string|null, ray_id: string|null, is_cloudflare: bool}
     */
    public function cloudflareInfo(): array
    {
        $server = $this->request->getServerParams();

        return [
            'ip' => $this->ipFromCloudflare(),
            'country' => $server['HTTP_CF_IPCOUNTRY'] ?? null,
            'ray_id' => $this->cloudflareRayId(),
            'is_cloudflare' => $this->isFromCloudflare(),
        ];
    }

    /**
     * Get user agent.
     *
     * @return string|null
     */
    public function userAgent(): ?string
    {
        $ua = $this->header('User-Agent');
        return is_string($ua) ? $ua : null;
    }

    /**
     * Get the referer.
     *
     * @return string|null
     */
    public function referer(): ?string
    {
        $referer = $this->header('Referer');
        return is_string($referer) ? $referer : null;
    }

    /**
     * Alias for referer().
     *
     * @return string|null
     */
    public function referrer(): ?string
    {
        return $this->referer();
    }

    /**
     * Get request attributes.
     *
     * @return array
     */
    public function attributes(): array
    {
        return $this->request->getAttributes();
    }

    /**
     * Get a request attribute (route parameter).
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * Alias for attribute().
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attribute($name, $default);
    }

    /**
     * Get route parameter (alias for attribute with better semantics).
     *
     * @param string|null $parameter
     * @param mixed $default
     *
     * @return mixed
     */
    public function route(?string $parameter = null, mixed $default = null): mixed
    {
        if ($parameter === null) {
            return $this->attributes();
        }

        return $this->attribute($parameter, $default);
    }

    /**
     * Set a request attribute.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function withAttribute(string $name, mixed $value): self
    {
        return new self($this->request->withAttribute($name, $value));
    }

    /**
     * Alias for withAttribute().
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function set(string $name, mixed $value): self
    {
        return $this->withAttribute($name, $value);
    }

    /**
     * Merge attributes into the request.
     *
     * @param array $attributes
     *
     * @return self
     */
    public function merge(array $attributes): self
    {
        $request = $this;

        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $request;
    }

    /**
     * Get the request fingerprint for rate limiting.
     *
     * @return string
     */
    public function fingerprint(): string
    {
        $components = [
            $this->path(),
            $this->ip(),
        ];

        if ($userId = $this->attribute('user_id')) {
            $components[] = $userId;
        }

        return sha1(implode('|', array_filter($components)));
    }

    /**
     * Get content type.
     *
     * @return string|null
     */
    public function contentType(): ?string
    {
        $contentType = $this->header('Content-Type');

        if (!is_string($contentType)) {
            return null;
        }

        // Remove charset and other parameters
        $parts = explode(';', $contentType);
        return trim($parts[0]);
    }

    /**
     * Check if request is from a specific IP or CIDR range.
     *
     * @param string|array<string> $ips
     *
     * @return bool
     */
    public function isFrom(string|array $ips): bool
    {
        $clientIp = $this->ip();

        if (!$clientIp) {
            return false;
        }

        $ips = is_array($ips) ? $ips : [$ips];

        return array_any($ips, fn($ip) => $this->ipMatches($clientIp, $ip));

    }

    /**
     * Validate if string is a valid IP address.
     *
     * @param string $ip
     *
     * @return bool
     */
    private function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Check if client IP matches given IP or CIDR range.
     *
     * @param string $clientIp
     * @param string $range
     *
     * @return bool
     */
    private function ipMatches(string $clientIp, string $range): bool
    {
        // Direct match
        if ($clientIp === $range) {
            return true;
        }

        // Check CIDR range
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);

            $clientIpLong = ip2long($clientIp);
            $subnetLong = ip2long($subnet);

            if ($clientIpLong === false || $subnetLong === false) {
                return false;
            }

            $mask = -1 << (32 - (int) $bits);
            return ($clientIpLong & $mask) === ($subnetLong & $mask);
        }

        return false;
    }
}