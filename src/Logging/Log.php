<?php

namespace ElliePHP\Components\Support\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class Log extends AbstractLogger
{
    private LoggerInterface $logger;
    private LoggerInterface $exceptionLogger;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?LoggerInterface $exceptionLogger = null
    )
    {
        $this->logger = $logger ?? new NullLogger();
        $this->exceptionLogger = $exceptionLogger ?? $this->logger;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function exception(Throwable $exception): void
    {
        $context = [
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'type' => get_class($exception),
        ];

        $this->exceptionLogger->critical($exception->getMessage(), $context);
    }
}