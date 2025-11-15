# ElliePHP Support

[![Latest Version](https://img.shields.io/packagist/v/elliephp/support.svg)](https://packagist.org/packages/elliephp/support)
[![License](https://img.shields.io/packagist/l/elliephp/support.svg)](https://packagist.org/packages/elliephp/support)
[![PHP Version](https://img.shields.io/packagist/php-v/elliephp/support.svg)](https://packagist.org/packages/elliephp/support)

A comprehensive collection of support utilities for PHP applications, providing helpers for strings, files, hashing, JSON, environment management, HTTP requests, and logging.

## Requirements

- PHP 8.4 or higher
- Extensions: `ctype`, `fileinfo`, `mbstring`, `simplexml`

## Installation

Install via Composer:

```bash
composer require elliephp/support
```

## Features

### String Utilities (`Str`)

Powerful string manipulation methods:

```php
use ElliePHP\Components\Support\Util\Str;

// Case conversion
Str::toCamelCase('hello-world');     // helloWorld
Str::toSnakeCase('HelloWorld');      // hello_world
Str::toKebabCase('HelloWorld');      // hello-world
Str::slug('Hello World!');           // hello-world

// String operations
Str::startsWith('hello', 'he');      // true
Str::contains('hello world', 'world'); // true
Str::limit('Long text...', 10);      // Long text...
Str::random(16);                     // Random string

// Validation
Str::isEmail('test@example.com');    // true
Str::isUrl('https://example.com');   // true
Str::isJson('{"key":"value"}');      // true
```

### Hash Utilities (`Hash`)

Comprehensive hashing and ID generation:

```php
use ElliePHP\Components\Support\Util\Hash;

// Password hashing
$hash = Hash::create('password');
Hash::check('password', $hash);      // true

// Various hash algorithms
Hash::sha256('data');
Hash::xxh3('data');
Hash::md5('data');

// ID generation
Hash::uuid();                        // UUID v4
Hash::ulid();                        // ULID
Hash::nanoid();                      // NanoID

// File hashing
Hash::file('/path/to/file');
```

### File Operations (`File`)

Complete file system utilities:

```php
use ElliePHP\Components\Support\Util\File;

// Read/Write
$content = File::get('/path/to/file.txt');
File::put('/path/to/file.txt', 'content');
File::append('/path/to/file.txt', 'more');

// File info
File::size('/path/to/file');
File::mimeType('/path/to/file');
File::extension('/path/to/file.txt'); // txt

// JSON operations
$data = File::json('/path/to/data.json');
File::putJson('/path/to/data.json', ['key' => 'value']);

// Directory operations
File::makeDirectory('/path/to/dir');
File::files('/path/to/dir');
File::deleteDirectory('/path/to/dir');
```

### JSON Utilities (`Json`)

Advanced JSON handling:

```php
use ElliePHP\Components\Support\Util\Json;

// Encode/Decode with error handling
$json = Json::encode(['key' => 'value']);
$data = Json::decode($json);

// Pretty printing
Json::pretty(['key' => 'value']);

// Dot notation access
Json::get($json, 'user.address.city', 'default');
Json::set($json, 'user.name', 'John');

// File operations
Json::fromFile('/path/to/file.json');
Json::toFile('/path/to/file.json', $data);

// Utilities
Json::flatten($json);
Json::merge($json1, $json2);
Json::validate($json, $schema);
```

### Environment Management (`Env`)

Easy .env file handling:

```php
use ElliePHP\Components\Support\Util\Env;

// Create instance and load .env file
$env = new Env(__DIR__);
$env->load();

// Get values with automatic type casting
$debug = $env->get('APP_DEBUG', false);  // bool
$port = $env->get('APP_PORT', 3000);     // int
$name = $env->get('APP_NAME', 'MyApp'); // string

// Check if variable exists
$env->has('APP_KEY');                    // true/false

// Require variables
$env->requireNotEmpty(['APP_KEY', 'DB_HOST']);
$env->requireOneOf('APP_ENV', ['local', 'production']);
```

### HTTP Request (`Request`)

PSR-7 compliant request wrapper:

```php
use ElliePHP\Components\Support\Http\Request;

$request = Request::fromGlobals();

// Input handling
$name = $request->input('name', 'default');
$email = $request->string('email');
$age = $request->int('age', 0);
$active = $request->bool('active');

// Request info
$request->method();              // GET, POST, etc.
$request->path();                // /api/users
$request->isJson();              // true/false
$request->bearerToken();         // JWT token

// Headers
$request->header('Content-Type');
$request->hasHeader('Authorization');
```

### HTTP Response (`Response`)

PSR-7 compliant response builder with extensive helpers:

```php
use ElliePHP\Components\Support\Http\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

$factory = new Psr17Factory();
$psrResponse = $factory->createResponse(200);
$response = new Response($psrResponse);

// Basic responses
$response->make('Hello World');
$response->make(['data' => 'value'], 200, ['X-Custom' => 'header']);

// Content type responses
$response->json(['status' => 'success']);
$response->jsonp('callback', ['data' => 'value']);
$response->html('<h1>Hello</h1>');
$response->text('Plain text content');
$response->xml('<?xml version="1.0"?><root></root>');

// Redirects
$response->redirect('/dashboard');
$response->redirectPermanent('/new-url');      // 301
$response->redirectTemporary('/temp-url');     // 302
$response->redirectSeeOther('/other');         // 303
$response->back('/fallback');                  // Previous URL

// Status code helpers (2xx)
$response->ok(['data' => 'value']);            // 200
$response->created(['id' => 123]);             // 201
$response->accepted(['queued' => true]);       // 202
$response->noContent();                        // 204

// Status code helpers (4xx)
$response->badRequest('Invalid input');        // 400
$response->unauthorized('Login required');     // 401
$response->forbidden('Access denied');         // 403
$response->notFound('Resource not found');     // 404
$response->methodNotAllowed(['GET', 'POST']);  // 405
$response->conflict('Resource exists');        // 409
$response->unprocessable(['errors' => []]);    // 422
$response->tooManyRequests(60, 'Rate limit');  // 429

// Status code helpers (5xx)
$response->serverError('System error');        // 500
$response->serviceUnavailable(300, 'Down');    // 503

// File downloads
$response->download($content, 'report.pdf');
$response->file('/path/to/document.pdf', 'custom-name.pdf');
$response->streamDownload('/path/to/large-file.zip', 'archive.zip');
$response->streamDownload('/tmp/file.txt', null, [], true); // Delete after

// Headers and cookies
$response->withHeader('X-Custom', 'value');
$response->withHeaders(['X-Foo' => 'bar', 'X-Baz' => 'qux']);
$response->contentType('application/json');
$response->cacheControl('max-age=3600');
$response->noCache();
$response->etag('abc123');
$response->lastModified(time());

// Cookies
$response->cookie('session', 'abc123', 60);    // Expires in 60 minutes
$response->withCookie('token', 'xyz', time() + 3600);
$response->withoutCookie('old_cookie');        // Delete cookie

// Response inspection
$response->status();                           // Get status code
$response->isSuccessful();                     // 2xx
$response->isOk();                             // 200
$response->isRedirect();                       // 3xx
$response->isClientError();                    // 4xx
$response->isServerError();                    // 5xx
$response->isForbidden();                      // 403
$response->isNotFound();                       // 404

// Body and headers
$response->body();                             // Get body as string
$response->content();                          // Alias for body()
$response->toJson();                           // Encode body as JSON
$response->headers();                          // Get all headers
$response->getHeader('Content-Type');          // Get specific header
$response->hasHeader('Authorization');         // Check header exists

// PSR-7 access
$psrResponse = $response->psr();               // Get PSR-7 response
$psrResponse = $response->raw();               // Alias for psr()

// Send response
$response->send();                             // Send to client
$response->sendAndExit();                      // Send and exit script

// Method chaining - Build complex responses fluently
$response->json(['user' => 'John'])
    ->withHeader('X-API-Version', '1.0')
    ->withHeader('X-Request-ID', 'abc123')
    ->cookie('session', 'token', 120)
    ->send();

// Chain status, headers, and body modifications
$response->make(['data' => 'value'])
    ->withStatus(201)
    ->withHeaders([
        'X-Custom' => 'header',
        'X-Rate-Limit' => '100'
    ])
    ->contentType('application/json')
    ->cacheControl('no-cache')
    ->send();

// Build API response with multiple cookies and headers
$response->ok(['success' => true])
    ->withCookie('auth', 'token123', time() + 3600)
    ->withCookie('refresh', 'refresh456', time() + 86400)
    ->withHeader('X-Total-Count', '150')
    ->etag('v1.2.3')
    ->lastModified(time())
    ->send();

// Create cacheable response
$response->json(['data' => 'cached'])
    ->cacheControl('public, max-age=3600')
    ->etag(md5('cached-data'))
    ->lastModified(strtotime('-1 hour'))
    ->send();

// Redirect with cookie
$response->redirect('/dashboard')
    ->cookie('last_page', '/profile', 30)
    ->withHeader('X-Redirect-Reason', 'authentication')
    ->send();

// No-cache JSON response with custom headers
$response->json(['realtime' => 'data'])
    ->noCache()
    ->withHeaders([
        'X-Stream-ID' => 'live-123',
        'X-Update-Frequency' => '5s'
    ])
    ->send();
```

### Logging (`Log`)

PSR-3 compliant logging facade:

```php
use ElliePHP\Components\Support\Logging\Log;
use Psr\Log\LoggerInterface;

// Inject your PSR-3 logger
$logger = new Log($psrLogger);

// Log messages
$logger->debug('Debug information');
$logger->info('User logged in', ['user_id' => 123]);
$logger->warning('Deprecated method used');
$logger->error('Database error', ['query' => $sql]);
$logger->critical('System failure');

// Log exceptions with full context
try {
    // code...
} catch (\Exception $e) {
    $logger->exception($e);
}
```

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test:coverage
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- [Joey Boli](https://github.com/elliephp)
- [All Contributors](https://github.com/elliephp/support/contributors)

## Support

- [Issues](https://github.com/elliephp/support/issues)
- [Source Code](https://github.com/elliephp/support)
