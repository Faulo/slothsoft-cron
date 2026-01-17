<?php
declare(strict_types = 1);
namespace Slothsoft\Cron;

use PHPUnit\Framework\TestCase;

/**
 * InstructionsParserTest
 *
 * @see InstructionsParser
 *
 * @todo auto-generated
 */
class InstructionsParserTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(InstructionsParser::class), "Failed to load class 'Slothsoft\Cron\InstructionsParser'!");
    }
}