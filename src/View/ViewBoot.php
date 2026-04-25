<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\View;

final class ViewBoot
{
    private TwigRendererBuilder $builder;

    public function __construct(string|array $paths = 'views')
    {
        $this->builder = TwigRenderer::builder($paths);
        $this->apply();
    }

    public function viewDirectory(string $path): self
    {
        $this->builder->path($path);
        return $this->apply();
    }

    /**
     * @param list<string> $paths
     */
    public function viewDirectories(array $paths): self
    {
        $this->builder->paths($paths);
        return $this->apply();
    }

    public function addViewDirectory(string $path): self
    {
        $this->builder->addPath($path);
        return $this->apply();
    }

    public function debug(bool $enabled = true): self
    {
        $this->builder->debug($enabled);
        return $this->apply();
    }

    public function charset(string $charset): self
    {
        $this->builder->charset($charset);
        return $this->apply();
    }

    /**
     * @param string|\Twig\Cache\CacheInterface|false $cache
     */
    public function cache(string|\Twig\Cache\CacheInterface|false $cache): self
    {
        $this->builder->cache($cache);
        return $this->apply();
    }

    public function autoReload(?bool $enabled): self
    {
        $this->builder->autoReload($enabled);
        return $this->apply();
    }

    public function strictVariables(bool $enabled = true): self
    {
        $this->builder->strictVariables($enabled);
        return $this->apply();
    }

    /**
     * @param string|false|callable $strategy
     */
    public function autoescape(string|false|callable $strategy): self
    {
        $this->builder->autoescape($strategy);
        return $this->apply();
    }

    public function optimizations(int $flags): self
    {
        $this->builder->optimizations($flags);
        return $this->apply();
    }

    public function useYield(bool $enabled = true): self
    {
        $this->builder->useYield($enabled);
        return $this->apply();
    }

    public function option(string $key, mixed $value): self
    {
        $this->builder->option($key, $value);
        return $this->apply();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function options(array $options): self
    {
        $this->builder->options($options);
        return $this->apply();
    }

    public function global(string $key, mixed $value): self
    {
        $this->builder->global($key, $value);
        return $this->apply();
    }

    /**
     * @param array<string, mixed> $globals
     */
    public function globals(array $globals): self
    {
        $this->builder->globals($globals);
        return $this->apply();
    }

    private function apply(): self
    {
        View::setRenderer($this->builder->build());
        return $this;
    }
}

