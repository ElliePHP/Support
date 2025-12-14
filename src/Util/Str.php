<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Util;

use Stringable;

final class Str
{
    /**
     * Normalize any incoming value into a valid UTF-8 string.
     * Accepts: string, Stringable, null, mixed.
     *
     * Ensures no TypeErrors occur anywhere in the class.
     */
    private static function normalizeString(mixed $value): string
    {
        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /** Convenience alias */
    private static function toStr(mixed $value): string
    {
        return self::normalizeString($value);
    }

    public static function startsWith(mixed $haystack, mixed $needle): bool
    {
        return str_starts_with(self::toStr($haystack), self::toStr($needle));
    }

    public static function startsWithAny(mixed $haystack, array $needles): bool
    {
        $haystack = self::toStr($haystack);
        return array_any($needles, static fn($needle) => self::startsWith($haystack, $needle));
    }

    public static function containsAny(mixed $haystack, array $needles): bool
    {
        $haystack = self::toStr($haystack);
        return array_any($needles, static fn($needle) => self::contains($haystack, $needle));
    }

    public static function endsWithAny(mixed $haystack, array $needles): bool
    {
        $haystack = self::toStr($haystack);
        return array_any($needles, static fn($needle) => self::endsWith($haystack, $needle));
    }

    public static function endsWith(mixed $haystack, mixed $needle): bool
    {
        $haystack = self::toStr($haystack);
        $needle = self::toStr($needle);
        return $needle === '' || str_ends_with($haystack, $needle);
    }

    public static function contains(mixed $haystack, mixed $needle): bool
    {
        return str_contains(self::toStr($haystack), self::toStr($needle));
    }

    public static function containsAll(mixed $haystack, array $needles): bool
    {
        $haystack = self::toStr($haystack);
        return array_all($needles, fn($needle) => self::contains($haystack, $needle));
    }

    public static function toCamelCase(mixed $string): string
    {
        $string = self::toStr($string);
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string))));
    }

    public static function toPascalCase(mixed $string): string
    {
        return ucfirst(self::toCamelCase($string));
    }

    public static function toSnakeCase(mixed $string): string
    {
        $string = self::toStr($string);
        return strtolower(preg_replace("/([a-z])([A-Z])/", '$1_$2', str_replace(' ', '_', $string)));
    }

    public static function toKebabCase(mixed $string): string
    {
        $string = self::toStr($string);
        return strtolower(preg_replace("/([a-z])([A-Z])/", '$1-$2', str_replace(' ', '-', $string)));
    }

    public static function limit(mixed $string, int $limit = 100, string $end = '...'): string
    {
        $string = self::toStr($string);
        return strlen($string) <= $limit ? $string : substr($string, 0, $limit) . $end;
    }

    public static function truncateWords(mixed $string, int $words = 10, string $end = '...'): string
    {
        $string = self::toStr($string);
        $parts = preg_split("/\s+/", $string) ?: [];
        return count($parts) <= $words ? $string : implode(' ', array_slice($parts, 0, $words)) . $end;
    }

    public static function words(mixed $string, int $words = 10): string
    {
        $string = trim(self::toStr($string));
        $parts = preg_split("/\s+/", $string) ?: [];
        return implode(' ', array_slice($parts, 0, $words));
    }

    public static function wordCount(mixed $string): int
    {
        $string = trim(self::toStr($string));
        return count(preg_split("/\s+/", $string, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    public static function clean(mixed $string): string
    {
        $string = self::toStr($string);
        $result = preg_replace("/[^\p{L}\p{N}\s]/u", "", $string);
        return $result === null ? '' : trim($result);
    }

    public static function replace(mixed $search, mixed $replace, mixed $subject): string
    {
        return str_replace(self::toStr($search), self::toStr($replace), self::toStr($subject));
    }

    public static function replaceFirst(mixed $search, mixed $replace, mixed $subject): string
    {
        $search = self::toStr($search);
        $replace = self::toStr($replace);
        $subject = self::toStr($subject);

        $pos = strpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    public static function replaceLast(mixed $search, mixed $replace, mixed $subject): string
    {
        $search = self::toStr($search);
        $replace = self::toStr($replace);
        $subject = self::toStr($subject);

        $pos = strrpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    public static function replaceArray(array $search, array $replace, mixed $subject): string
    {
        $subject = self::toStr($subject);
        return str_replace($search, $replace, $subject);
    }

    public static function toUpperCase(mixed $string): string
    {
        return strtoupper(self::toStr($string));
    }

    public static function toLowerCase(mixed $string, ?string $encoding = 'UTF-8'): string
    {
        return mb_strtolower(self::toStr($string), $encoding ?? 'UTF-8');
    }

    public static function title(mixed $string): string
    {
        return ucwords(self::toLowerCase($string));
    }

    public static function ucfirst(mixed $string): string
    {
        return ucfirst(self::toStr($string));
    }

    public static function lcfirst(mixed $string): string
    {
        return lcfirst(self::toStr($string));
    }

    public static function reverse(mixed $string): string
    {
        return strrev(self::toStr($string));
    }

    public static function slug(mixed $string, string $separator = '-'): string
    {
        $string = self::toStr($string);
        $string = strtolower(trim((string) preg_replace("/[^A-Za-z0-9-]+/", $separator, $string)));
        return trim($string, $separator);
    }

    public static function length(mixed $string): int
    {
        return mb_strlen(self::toStr($string));
    }

    public static function isEmpty(mixed $string): bool
    {
        return trim(self::toStr($string)) === '';
    }

    public static function isNotEmpty(mixed $string): bool
    {
        return !self::isEmpty($string);
    }

    public static function padLeft(mixed $string, int $length, string $pad = ' '): string
    {
        return str_pad(self::toStr($string), $length, $pad, STR_PAD_LEFT);
    }

    public static function padRight(mixed $string, int $length, string $pad = ' '): string
    {
        return str_pad(self::toStr($string), $length, $pad);
    }

    public static function padBoth(mixed $string, int $length, string $pad = ' '): string
    {
        return str_pad(self::toStr($string), $length, $pad, STR_PAD_BOTH);
    }

    public static function match(mixed $pattern, mixed $subject): ?array
    {
        $subject = self::toStr($subject);
        return preg_match(self::toStr($pattern), $subject, $matches) ? $matches : null;
    }

    public static function matchAll(mixed $pattern, mixed $subject): ?array
    {
        $subject = self::toStr($subject);
        return preg_match_all(self::toStr($pattern), $subject, $matches) ? $matches : null;
    }

    public static function random(int $length = 16): string
    {
        return substr(
            str_shuffle(str_repeat(
                "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
                (int) ceil($length / 62)
            )),
            0,
            $length
        );
    }

    public static function extractStringBetween(mixed $string, mixed $start, mixed $end): ?string
    {
        $string = self::toStr($string);
        $start = self::toStr($start);
        $end   = self::toStr($end);

        $startPos = strpos($string, $start);
        if ($startPos === false) {
            return null;
        }

        $endPos = strpos($string, $end, $startPos + strlen($start));
        if ($endPos === false) {
            return null;
        }

        return mb_substr($string, $startPos + strlen($start), $endPos - $startPos - strlen($start));
    }

    public static function substr(mixed $string, int $start, ?int $length = null): string
    {
        return mb_substr(self::toStr($string), $start, $length);
    }

    public static function before(mixed $string, mixed $search): string
    {
        $string = self::toStr($string);
        $search = self::toStr($search);

        $pos = strpos($string, $search);
        return $pos === false ? $string : substr($string, 0, $pos);
    }

    public static function after(mixed $string, mixed $search): string
    {
        $string = self::toStr($string);
        $search = self::toStr($search);

        $pos = strpos($string, $search);
        return $pos === false ? '' : substr($string, $pos + strlen($search));
    }

    public static function beforeLast(mixed $string, mixed $search): string
    {
        $string = self::toStr($string);
        $search = self::toStr($search);

        $pos = strrpos($string, $search);
        return $pos === false ? $string : substr($string, 0, $pos);
    }

    public static function afterLast(mixed $string, mixed $search): string
    {
        $string = self::toStr($string);
        $search = self::toStr($search);

        $pos = strrpos($string, $search);
        return $pos === false ? '' : substr($string, $pos + strlen($search));
    }

    public static function repeat(mixed $string, int $times): string
    {
        return str_repeat(self::toStr($string), $times);
    }

    public static function trim(mixed $string, string $characters = " \t\n\r\0\x0B"): string
    {
        return trim(self::toStr($string), $characters);
    }

    public static function ltrim(mixed $string, string $characters = " \t\n\r\0\x0B"): string
    {
        return ltrim(self::toStr($string), $characters);
    }

    public static function rtrim(mixed $string, string $characters = " \t\n\r\0\x0B"): string
    {
        return rtrim(self::toStr($string), $characters);
    }

    public static function removePrefix(mixed $string, mixed $prefix): string
    {
        $string = self::toStr($string);
        $prefix = self::toStr($prefix);

        return self::startsWith($string, $prefix)
            ? substr($string, strlen($prefix))
            : $string;
    }

    public static function removeSuffix(mixed $string, mixed $suffix): string
    {
        $string = self::toStr($string);
        $suffix = self::toStr($suffix);

        return self::endsWith($string, $suffix)
            ? substr($string, 0, -strlen($suffix))
            : $string;
    }

    public static function ensurePrefix(mixed $string, mixed $prefix): string
    {
        $string = self::toStr($string);
        $prefix = self::toStr($prefix);

        return self::startsWith($string, $prefix)
            ? $string
            : $prefix . $string;
    }

    public static function ensureSuffix(mixed $string, mixed $suffix): string
    {
        $string = self::toStr($string);
        $suffix = self::toStr($suffix);

        return self::endsWith($string, $suffix)
            ? $string
            : $string . $suffix;
    }

    public static function toArray(mixed $string): array
    {
        return preg_split("//u", self::toStr($string), -1, PREG_SPLIT_NO_EMPTY)
            ?: [];
    }

    public static function isJson(mixed $string): bool
    {
        if (!is_string($string) && !is_scalar($string) && !$string instanceof Stringable) {
            return false;
        }

        $string = (string) $string;

        return json_validate($string);
    }

    public static function isUrl(mixed $string): bool
    {
        return filter_var(self::toStr($string), FILTER_VALIDATE_URL) !== false;
    }

    public static function isEmail(mixed $string): bool
    {
        return filter_var(self::toStr($string), FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isAlphanumeric(mixed $string): bool
    {
        return ctype_alnum(self::toStr($string));
    }

    public static function isAlpha(mixed $string): bool
    {
        return ctype_alpha(self::toStr($string));
    }

    public static function isNumeric(mixed $string): bool
    {
        return is_numeric(self::toStr($string));
    }

    public static function mask(
        mixed $string,
        string $character = '*',
        int $index = 0,
        ?int $length = null
    ): string {
        $string = self::toStr($string);

        if ($index === 0 && $length === null) {
            return str_repeat($character, mb_strlen($string));
        }

        $segment = mb_substr($string, $index, $length);
        $strlen = mb_strlen($string);
        $startIdx = $index < 0 ? $index + $strlen : $index;
        $start = mb_substr($string, 0, $startIdx);
        $segmentLen = mb_strlen($segment);
        $end = mb_substr($string, $startIdx + $segmentLen);

        return $start . str_repeat($character, $segmentLen) . $end;
    }

    public static function swap(mixed $string, array $replacements): string
    {
        return strtr(self::toStr($string), $replacements);
    }

    public static function split(string $separator, mixed $string, int $limit = PHP_INT_MAX): array
    {
        return explode($separator, self::toStr($string), $limit);
    }

    public static function cleanUtf8(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        return mb_convert_encoding($value, "UTF-8", "UTF-8");
    }

    public static function plural(mixed $string, int $count = 2): string
    {
        $string = self::toStr($string);

        if ($count === 1) {
            return $string;
        }

        $rules = [
            '/(quiz)$/i' => "$1zes",
            '/^(ox)$/i' => "$1en",
            '/([m|l])ouse$/i' => "$1ice",
            '/(matr|vert|ind)ix|ex$/i' => "$1ices",
            '/(x|ch|ss|sh)$/i' => "$1es",
            '/([^aeiouy]|qu)y$/i' => "$1ies",
            '/(hive)$/i' => "$1s",
            '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
            '/(shea|lea|loa|thie)f$/i' => "$1ves",
            '/sis$/i' => "ses",
            '/([ti])um$/i' => "$1a",
            '/(tomat|potat|ech|her|vet)o$/i' => "$1oes",
            '/(bu)s$/i' => "$1ses",
            '/(alias)$/i' => "$1es",
            '/(octop)us$/i' => "$1i",
            '/(ax|test)is$/i' => "$1es",
            '/(us)$/i' => "$1es",
            '/s$/i' => "s",
            '/$/' => "s"
        ];

        foreach ($rules as $rule => $replacement) {
            if (preg_match($rule, $string)) {
                return preg_replace($rule, $replacement, $string);
            }
        }

        return $string;
    }

    public static function singular(mixed $string): string
    {
        $string = self::toStr($string);

        $rules = [
            '/(quiz)zes$/i' => "$1",
            '/(matr)ices$/i' => "$1ix",
            '/(vert|ind)ices$/i' => "$1ex",
            '/^(ox)en$/i' => "$1",
            '/(alias)es$/i' => "$1",
            '/(octop|vir)i$/i' => "$1us",
            '/(cris|ax|test)es$/i' => "$1is",
            '/(shoe)s$/i' => "$1",
            '/(o)es$/i' => "$1",
            '/(bus)es$/i' => "$1",
            '/([m|l])ice$/i' => "$1ouse",
            '/(x|ch|ss|sh)es$/i' => "$1",
            '/(m)ovies$/i' => "$1ovie",
            '/(s)eries$/i' => "$1eries",
            '/([^aeiouy]|qu)ies$/i' => "$1y",
            '/([lr])ves$/i' => "$1f",
            '/(tive)s$/i' => "$1",
            '/(hive)s$/i' => "$1",
            '/(li|wi|kni)ves$/i' => "$1fe",
            '/(shea|loa|lea|thie)ves$/i' => "$1f",
            '/(^analy)ses$/i' => "$1sis",
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => "$1$2sis",
            '/([ti])a$/i' => "$1um",
            '/(n)ews$/i' => "$1ews",
            '/(h|bl)ouses$/i' => "$1ouse",
            '/(corpse)s$/i' => "$1",
            '/(us)es$/i' => "$1",
            '/s$/i' => ""
        ];

        foreach ($rules as $rule => $replacement) {
            if (preg_match($rule, $string)) {
                return preg_replace($rule, $replacement, $string);
            }
        }

        return $string;
    }
}
