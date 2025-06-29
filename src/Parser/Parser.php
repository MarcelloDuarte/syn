<?php

declare(strict_types=1);

namespace Syn\Parser;

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Syn\Parser\Combinators\ParserCombinator;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\Combinators\SequenceParser;
use Syn\Parser\Combinators\ChoiceParser;
use Syn\Parser\Combinators\ManyParser;
use Syn\Parser\Combinators\OptionalParser;

class Parser
{
    private \PhpParser\Parser $phpParser;
    private ParserCombinator $macroParser;
    private array $tokens = [];

    public function __construct()
    {
        $this->phpParser = (new ParserFactory())->createForHostVersion();
        $this->macroParser = $this->buildMacroParser();
    }

    public function parse(string $code): array
    {
        try {
            $ast = $this->phpParser->parse($code);
            return $ast ?? [];
        } catch (\PhpParser\Error $e) {
            throw new ParserException("PHP parsing error: " . $e->getMessage(), 0, $e);
        }
    }

    public function parseMacro(string $macroCode): MacroDefinition
    {
        $tokens = token_get_all($macroCode);
        $this->tokens = $tokens;
        
        $result = $this->macroParser->parse($tokens, 0);
        
        if (!$result->isSuccess()) {
            throw new ParserException("Macro parsing failed: " . $result->getError());
        }
        
        return $result->getValue();
    }

    public function tokenize(string $code): array
    {
        return token_get_all($code);
    }

    private function buildMacroParser(): ParserCombinator
    {
        // Basic macro pattern: $(macro) { pattern } >> { replacement }
        $macroKeyword = new TokenParser(T_STRING, 'macro');
        $openParen = new TokenParser('(');
        $closeParen = new TokenParser(')');
        $openBrace = new TokenParser('{');
        $closeBrace = new TokenParser('}');
        $arrow = new TokenParser(T_DOUBLE_ARROW, '>>');
        
        $pattern = new ManyParser(new TokenParser(T_WHITESPACE));
        $replacement = new ManyParser(new TokenParser(T_WHITESPACE));
        
        $macroPattern = new SequenceParser([
            $openParen,
            $macroKeyword,
            $closeParen,
            $openBrace,
            $pattern,
            $closeBrace,
            $arrow,
            $openBrace,
            $replacement,
            $closeBrace
        ]);
        
        return $macroPattern;
    }

    public function parseWithLineNumbers(string $code): array
    {
        $ast = $this->parse($code);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class extends NodeVisitorAbstract {
            public function enterNode(Node $node) {
                if ($node->hasAttribute('startLine')) {
                    $node->setAttribute('originalLine', $node->getAttribute('startLine'));
                }
                return null;
            }
        });
        
        return $traverser->traverse($ast);
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }
} 
