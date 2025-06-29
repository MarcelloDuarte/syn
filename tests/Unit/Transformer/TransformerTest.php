<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Transformer;

use PHPUnit\Framework\TestCase;
use Syn\Core\Configuration;
use Syn\Transformer\Transformer;

class TransformerTest extends TestCase
{
    private Transformer $transformer;

    protected function setUp(): void
    {
        $config = new Configuration();
        $this->transformer = new Transformer($config);
    }

    public function testTokenizeSimpleCode(): void
    {
        $code = '<?php $->name;';
        $tokens = token_get_all($code);
        $this->assertNotEmpty($tokens);
    }

    public function testPatternTokenization(): void
    {
        $macroLoader = $this->transformer->getMacroLoader();
        $reflection = new \ReflectionClass($macroLoader);
        $method = $reflection->getMethod('tokenizePattern');
        $method->setAccessible(true);
        
        $pattern = '$->';
        $tokens = $method->invoke($macroLoader, $pattern);
        $this->assertEquals(['$', '->'], $tokens);
    }

    public function testSimpleMacroReplacement(): void
    {
        // Set up a simple macro
        $macroLoader = $this->transformer->getMacroLoader();
        $macroLoader->loadFromString('$(macro) { $-> } >> { $this-> }');
        
        $input = '<?php return $->name;';
        $result = $this->transformer->transform($input, 'test.php');
        $this->assertStringContainsString('$this->', $result);
    }

    public function testMacroMatching(): void
    {
        $macroLoader = $this->transformer->getMacroLoader();
        $macroLoader->loadFromString('$(macro) { $-> } >> { $this-> }');
        
        $tokens = token_get_all('<?php $->name;');
        // Test token sequence matching starting from position 1 (skip opening tag)
        $matchingMacros = $macroLoader->findMacrosForTokenSequence($tokens, 1);
        $this->assertNotEmpty($matchingMacros);
    }
} 
