<?php

declare(strict_types=1);

namespace Syn\Parser;

class ParserException extends \Exception
{
    private ?string $sourceFile = null;
    private ?int $sourceLine = null;
    private ?int $sourceColumn = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $sourceFile = null,
        ?int $sourceLine = null,
        ?int $sourceColumn = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->sourceFile = $sourceFile;
        $this->sourceLine = $sourceLine;
        $this->sourceColumn = $sourceColumn;
    }

    public function getSourceFile(): ?string
    {
        return $this->sourceFile;
    }

    public function getSourceLine(): ?int
    {
        return $this->sourceLine;
    }

    public function getSourceColumn(): ?int
    {
        return $this->sourceColumn;
    }

    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();
        
        if ($this->sourceFile) {
            $message = "File: {$this->sourceFile} - " . $message;
        }
        
        if ($this->sourceLine) {
            $message .= " (line {$this->sourceLine}";
            if ($this->sourceColumn) {
                $message .= ", column {$this->sourceColumn}";
            }
            $message .= ")";
        }
        
        return $message;
    }
} 
