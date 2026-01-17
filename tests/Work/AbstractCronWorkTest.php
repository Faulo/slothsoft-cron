<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use PHPUnit\Framework\TestCase;

/**
 * AbstractCronWorkTest
 *
 * @see AbstractCronWork
 *
 * @todo auto-generated
 */
class AbstractCronWorkTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(AbstractCronWork::class), "Failed to load class 'Slothsoft\Cron\Work\AbstractCronWork'!");
    }
}