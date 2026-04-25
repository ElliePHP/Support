<?php

declare(strict_types=1);

use ElliePHP\Components\Support\View\View;
use ElliePHP\Components\Support\View\TwigRenderer;

if (!function_exists('view')) {
    /**
     * Render a Twig view from a directory (or directories).
     *
     * @param string $template Template name (e.g. "home.twig")
     * @param array<string, mixed> $data Data passed to the template
     * @param string|list<string> $paths One or more template directories
     * @param array<string, mixed> $options Twig Environment options
     * @param array<string, mixed> $globals Global variables available in every template
     */
    function view(
        string       $template,
        array        $data = [],
        string|array $paths = 'views',
        array        $options = [],
        array        $globals = []
    ): string
    {
        try {
            return View::render($template, $data);
        } catch (\Throwable) {
            // Fallback for non-bootstrapped usage
            return (new TwigRenderer($paths, $options, $globals))->render($template, $data);
        }
    }
}

