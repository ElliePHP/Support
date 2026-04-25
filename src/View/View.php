<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\View;

use RuntimeException;

/**
 * Simple renderer registry for bootstrap-based apps.
 */
final class View
{
    private static ?TwigRenderer $renderer = null;

    /**
     * Boots the (Twig) view renderer for your app.
     *
     * This returns a fluent boot object. You can chain methods without needing
     * a final "apply" call; each chain update reconfigures the renderer.
     *
     * @param string|list<string> $paths One or more template directories (defaults to "views")
     * @param array<string, mixed> $options Twig Environment options (same keys as `\Twig\Environment`)
     * @param array<string, mixed> $globals Global variables available in every template
     */
    public static function boot(string|array $paths = 'views', array $options = [], array $globals = []): ViewBoot
    {
        $boot = new ViewBoot($paths);

        if ($options !== []) {
            $boot->options($options);
        }

        if ($globals !== []) {
            $boot->globals($globals);
        }

        return $boot;
    }

    public static function setRenderer(TwigRenderer $renderer): void
    {
        self::$renderer = $renderer;
    }

    public static function renderer(): TwigRenderer
    {
        if (self::$renderer === null) {
            throw new RuntimeException(
                'No view renderer configured. Call ' . self::class . '::boot() in your bootstrap.'
            );
        }

        return self::$renderer;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        return self::renderer()->render($template, $data);
    }
}

