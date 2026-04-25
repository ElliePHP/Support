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

    public static function setRenderer(TwigRenderer $renderer): void
    {
        self::$renderer = $renderer;
    }

    public static function renderer(): TwigRenderer
    {
        if (self::$renderer === null) {
            throw new RuntimeException(
                'No view renderer configured. Call ' . self::class . '::setRenderer() in your bootstrap.'
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

