<?php

declare(strict_types=1);

namespace Syn\Parser;

class ParseResult
{
    private bool $success;
    private mixed $value;
    private int $position;
    private ?string $error;

    private function __construct(bool $success, mixed $value, int $position, ?string $error = null)
    {
        $this->success = $success;
        $this->value = $value;
        $this->position = $position;
        $this->error = $error;
    }

    public static function success(mixed $value, int $position): self
    {
        return new self(true, $value, $position);
    }

    public static function failure(int $position, string $error): self
    {
        return new self(false, null, $position, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getValue(): mixed
    {
        if (!$this->success) {
            throw new \RuntimeException("Cannot get value from failed parse result");
        }
        return $this->value;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function map(callable $fn): self
    {
        if (!$this->success) {
            return $this;
        }
        return self::success($fn($this->value), $this->position);
    }

    public function flatMap(callable $fn): self
    {
        if (!$this->success) {
            return $this;
        }
        return $fn($this->value);
    }
} 
