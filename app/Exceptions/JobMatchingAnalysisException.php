<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class JobMatchingAnalysisException extends RuntimeException
{
    /**
     * @param  array<int,array<string,mixed>>  $attemptedDrivers
     */
    public function __construct(
        string $message,
        private readonly string $failureType = 'unknown',
        private readonly bool $retryable = false,
        private readonly array $attemptedDrivers = [],
        private readonly ?string $driver = null,
        private readonly ?string $model = null,
        private readonly ?int $latencyMs = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function failureType(): string
    {
        return $this->failureType;
    }

    public function retryable(): bool
    {
        return $this->retryable;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function attemptedDrivers(): array
    {
        return $this->attemptedDrivers;
    }

    public function driver(): ?string
    {
        return $this->driver;
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function latencyMs(): ?int
    {
        return $this->latencyMs;
    }
}
