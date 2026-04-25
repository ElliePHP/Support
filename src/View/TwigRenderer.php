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
     * @param array<string, mixed> $options Twig Environment options (cache, debug, autoescape, strict_variables, etc.)
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

