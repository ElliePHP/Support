<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigRenderer
{
    private Environment $twig;

    /**
     * @param string|list<string> $paths One or more directories containing Twig templates
     * @param array<string, mixed> $options Twig Environment options. Supported keys are the same as `\Twig\Environment`.
     *
     * Available options:
     *
     * - **debug** (`bool`): Enables debug mode. When `true`, Twig also defaults `auto_reload` to `true`. Default: `false`.
     * - **charset** (`string`): Charset used by templates. Default: `'UTF-8'`.
     * - **cache** (`string|\Twig\Cache\CacheInterface|false`): Compilation cache. Provide an absolute path, a cache implementation,
     *   or `false` to disable. Default: `false`.
     * - **auto_reload** (`bool|null`): Whether to recompile templates when sources change. If `null`, Twig decides based on `debug`.
     *   Default: `null`.
     * - **strict_variables** (`bool`): When `true`, Twig throws on undefined variables/attributes/methods. Default: `false`.
     * - **autoescape** (`string|false|callable`): Default auto-escaping strategy. Common values: `'html'`, `'js'`, `'css'`, `'url'`,
     *   `'html_attr'`, `'name'`, `false`, or a callable `fn(string $templateName): string|false`. Default: `'html'`.
     * - **optimizations** (`int`): Optimization flags. Default: `-1` (all enabled). Use `0` to disable.
     * - **use_yield** (`bool`): When `true`, forces templates to only use `yield` instead of `echo` (extensions must be yield-ready).
     *   Default: `false`.
     *
     * See Twig docs for details: `https://twig.symfony.com/doc/3.x/api.html#environment-options`
     * @param array<string, mixed> $globals Global variables available in every template
     */
    public function __construct(string|array $paths, array $options = [], array $globals = [])
    {
        $paths = is_array($paths) ? $paths : [$paths];

        $loader = new FilesystemLoader($paths);
        $this->twig = new Environment($loader, $options);

        foreach ($globals as $key => $value) {
            $this->twig->addGlobal((string) $key, $value);
        }
    }

    public static function builder(string|array $paths = []): TwigRendererBuilder
    {
        return new TwigRendererBuilder($paths);
    }

    public function env(): Environment
    {
        return $this->twig;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }
}

