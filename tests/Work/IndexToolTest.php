<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use PHPUnit\Framework\TestCase;

/**
 * IndexToolTest
 *
 * @see IndexTool
 *
 * @todo auto-generated
 */
class IndexToolTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(IndexTool::class), "Failed to load class 'Slothsoft\Cron\Work\IndexTool'!");
    }
}