<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use PHPUnit\Framework\TestCase;

/**
 * IndexPhpTest
 *
 * @see IndexPhp
 *
 * @todo auto-generated
 */
final class IndexPhpTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(IndexPhp::class), "Failed to load class 'Slothsoft\Cron\Work\IndexPhp'!");
    }
}