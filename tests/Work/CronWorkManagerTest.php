<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use PHPUnit\Framework\TestCase;

/**
 * CronWorkManagerTest
 *
 * @see CronWorkManager
 *
 * @todo auto-generated
 */
final class CronWorkManagerTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(CronWorkManager::class), "Failed to load class 'Slothsoft\Cron\Work\CronWorkManager'!");
    }
}