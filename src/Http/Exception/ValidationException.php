<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Http\Exception;

use Exception;
use Rakit\Validation\ErrorBag;

class ValidationException extends Exception
{
    protected ErrorBag $errors;

    /**
     * Create a new validation exception instance.
     *
     * @param ErrorBag $errors
     * @param string $message
     */
    public function __construct(ErrorBag $errors, string $message = "The given data was invalid.")
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors->toArray();
    }

    /**
     * Get the raw ErrorBag instance.
     *
     * @return ErrorBag
     */
    public function getErrorBag(): ErrorBag
    {
        return $this->errors;
    }

    /**
     * Get the first error message for a given field.
     *
     * @param string $key
     * @return string|null
     */
    public function first(string $key): ?string
    {
        return $this->errors->first($key);
    }
}