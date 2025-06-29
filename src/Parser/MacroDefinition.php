<?php

declare(strict_types=1);

namespace Syn\Parser;

class MacroDefinition
{
    private string $pattern;
    private string $replacement;
    private array $options;
    private ?string $name;
    private ?string $file;
    private ?int $line;
    private array $parsedPattern = [];
    private array $captures = [];
    private array $parsedReplacement = [];

    public function __construct(
        string $pattern,
        string $replacement,
        array $options = [],
        ?string $name = null,
        ?string $file = null,
        ?int $line = null
    ) {
        $this->pattern = $pattern;
        $this->replacement = $replacement;
        $this->options = $options;
        $this->name = $name;
        $this->file = $file;
        $this->line = $line;
        
        // Parse the pattern and replacement for captures
        $this->parsePattern();
        $this->parseReplacement();
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }

    public function getParsedPattern(): array
    {
        return $this->parsedPattern;
    }

    public function getCaptures(): array
    {
        return $this->captures;
    }

    public function getParsedReplacement(): array
    {
        return $this->parsedReplacement;
    }

    public function hasCaptures(): bool
    {
        return !empty($this->captures);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $option): bool
    {
        return isset($this->options[$option]);
    }

    public function getOption(string $option, mixed $default = null): mixed
    {
        return $this->options[$option] ?? $default;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function isUnsafe(): bool
    {
        return $this->hasOption('unsafe') && $this->getOption('unsafe') === true;
    }

    public function getPriority(): int
    {
        return $this->getOption('priority', 0);
    }

    public function setPriority(int $priority): self
    {
        $this->options['priority'] = $priority;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'pattern' => $this->pattern,
            'replacement' => $this->replacement,
            'options' => $this->options,
            'name' => $this->name,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['pattern'] ?? '',
            $data['replacement'] ?? '',
            $data['options'] ?? [],
            $data['name'] ?? null,
            $data['file'] ?? null,
            $data['line'] ?? null
        );
    }

    private function parsePattern(): void
    {
        $this->parsedPattern = [];
        $this->captures = [];
        
        // Parse the pattern to identify captures like $(layer() as name)
        $pattern = $this->pattern;
        $captureIndex = 0;
        
        // Replace capture patterns with placeholders
        $pattern = preg_replace_callback(
            '/\$\(layer\(\)\s+as\s+(\w+)\)/',
            function($matches) use (&$captureIndex) {
                $captureName = $matches[1];
                $captureId = '__CAPTURE_' . $captureIndex . '__';
                $this->captures[$captureId] = $captureName;
                $captureIndex++;
                return $captureId;
            },
            $pattern
        );
        
        // Tokenize the modified pattern generically
        $this->parsedPattern = $this->tokenizeGeneric($pattern);
    }
    
    private function tokenizeGeneric(string $input): array
    {
        // Generic tokenizer that handles capture placeholders and delimiters
        $tokens = [];
        $i = 0;
        $len = strlen($input);
        $currentToken = '';
        
        while ($i < $len) {
            $char = $input[$i];
            
            // Check for capture placeholders
            if ($char === '_' && substr($input, $i, 10) === '__CAPTURE_') {
                // Save current token if any
                if (!empty($currentToken)) {
                    $tokens[] = trim($currentToken);
                    $currentToken = '';
                }
                
                // Find the end of the capture placeholder
                $endPos = strpos($input, '__', $i + 10);
                if ($endPos !== false) {
                    $captureToken = substr($input, $i, $endPos - $i + 2);
                    $tokens[] = $captureToken;
                    $i = $endPos + 2;
                    continue;
                }
            }
            
            // Handle delimiters and whitespace
            if (in_array($char, ['(', ')', '{', '}', '[', ']', ' ', "\t", "\n"])) {
                if (!empty($currentToken)) {
                    $tokens[] = trim($currentToken);
                    $currentToken = '';
                }
                if (!in_array($char, [' ', "\t", "\n"])) {
                    $tokens[] = $char;
                }
            } else {
                $currentToken .= $char;
            }
            
            $i++;
        }
        
        if (!empty($currentToken)) {
            $tokens[] = trim($currentToken);
        }
        
        return array_filter($tokens, fn($token) => $token !== '');
    }
    
    private function parseReplacement(): void
    {
        $this->parsedReplacement = [];
        
        // Parse the replacement to identify variable references like $(name)
        $replacement = $this->replacement;
        
        // Replace variable references with placeholders
        $replacement = preg_replace_callback(
            '/\$\((\w+)\)/',
            function($matches) {
                return '__VAR_' . $matches[1] . '__';
            },
            $replacement
        );
        
        // Tokenize the modified replacement
        if (!empty($replacement)) {
            $phpCode = '<?php ' . $replacement;
            $tokens = token_get_all($phpCode);
            
            // Remove the opening tag and convert to simple format
            foreach ($tokens as $token) {
                if (is_array($token)) {
                    // Skip T_OPEN_TAG
                    if ($token[0] === T_OPEN_TAG) {
                        continue;
                    }
                    $this->parsedReplacement[] = $token[1];
                } else {
                    $this->parsedReplacement[] = $token;
                }
            }
        }
    }
} 
