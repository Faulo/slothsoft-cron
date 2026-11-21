<?php
declare(strict_types = 1);

namespace Slothsoft\Cron;

use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InstructionsParserTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(InstructionsParser::class), "Failed to load class 'Slothsoft\Cron\InstructionsParser'!");
    }
    
    public function testRejectsDocumentWithoutRootElement(): void {
        $parser = new InstructionsParser();
        
        $this->expectException(InvalidArgumentException::class);
        $parser->fromDocument(new DOMDocument());
    }
}
