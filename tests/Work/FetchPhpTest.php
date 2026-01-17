<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use PHPUnit\Framework\TestCase;

/**
 * FetchPhpTest
 *
 * @see FetchPhp
 *
 * @todo auto-generated
 */
final class FetchPhpTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(FetchPhp::class), "Failed to load class 'Slothsoft\Cron\Work\FetchPhp'!");
    }
}