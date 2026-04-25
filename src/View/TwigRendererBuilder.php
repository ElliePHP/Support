<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\View;

final class TwigRendererBuilder
{
    /** @var list<string> */
    private array $paths;

    /** @var array<string, mixed> */
    private array $options = [];

    /** @var array<string, mixed> */
    private array $globals = [];

    public function __construct(string|array $paths = [])
    {
        if ($paths === []) {
            $this->paths = [];
            return;
        }

        $this->paths = is_array($paths) ? array_values($paths) : [$paths];
    }

    public function path(string $path): self
    {
        $this->paths = [$path];
        return $this;
    }

    /**
     * @param list<string> $paths
     */
    public function paths(array $paths): self
    {
        $this->paths = array_values($paths);
        return $this;
    }

    public function addPath(string $path): self
    {
        $this->paths[] = $path;
        return $this;
    }

    public function debug(bool $enabled = true): self
    {
        $this->options['debug'] = $enabled;
        return $this;
    }

    public function charset(string $charset): self
    {
        $this->options['charset'] = $charset;
        return $this;
    }

    /**
     * @param string|\Twig\Cache\CacheInterface|false $cache
     */
    public function cache(string|\Twig\Cache\CacheInterface|false $cache): self
    {
        $this->options['cache'] = $cache;
        return $this;
    }

    public function autoReload(?bool $enabled): self
    {
        $this->options['auto_reload'] = $enabled;
        return $this;
    }

    public function strictVariables(bool $enabled = true): self
    {
        $this->options['strict_variables'] = $enabled;
        return $this;
    }

    /**
     * @param string|false|callable $strategy
     */
    public function autoescape(string|false|callable $strategy): self
    {
        $this->options['autoescape'] = $strategy;
        return $this;
    }

    public function optimizations(int $flags): self
    {
        $this->options['optimizations'] = $flags;
        return $this;
    }

    public function useYield(bool $enabled = true): self
    {
        $this->options['use_yield'] = $enabled;
        return $this;
    }

    public function option(string $key, mixed $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function options(array $options): self
    {
        $this->options = $options + $this->options;
        return $this;
    }

    public function global(string $key, mixed $value): self
    {
        $this->globals[$key] = $value;
        return $this;
    }

    /**
     * @param array<string, mixed> $globals
     */
    public function globals(array $globals): self
    {
        $this->globals = $globals + $this->globals;
        return $this;
    }

    public function build(): TwigRenderer
    {
        $paths = $this->paths === [] ? 'views' : $this->paths;
        return new TwigRenderer($paths, $this->options, $this->globals);
    }
}

