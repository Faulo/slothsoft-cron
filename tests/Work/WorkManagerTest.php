<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use PHPUnit\Framework\TestCase;

final class WorkManagerTest extends TestCase {
    public function testOneWorkerDrainsRecursiveJobsBeforeTheBarrier(): void {
        $manager = new WorkManager(1);
        $manager->thenDo(SchedulerProbe::class, ['action' => 'parent'])
            ->thenDo(SchedulerProbe::class, ['action' => 'sibling'])
            ->thenWait()
            ->thenDo(SchedulerProbe::class, ['action' => 'next phase']);
        
        $chunks = iterator_to_array($manager->toChunks(), false);
        self::assertCount(5, $chunks);
        foreach (['parent', 'sibling', 'child', 'grandchild', 'next phase'] as $index => $expected) {
            self::assertStringContainsString(': ' . $expected . ' ', $chunks[$index]);
        }
    }
    
    public function testJobFailureIsLoggedAndRemainingWorkCompletes(): void {
        $manager = new WorkManager(1);
        $manager->thenDo(SchedulerProbe::class, ['action' => 'fail'])
            ->thenWait()
            ->thenDo(SchedulerProbe::class, ['action' => 'next phase']);
        
        $chunks = iterator_to_array($manager->toChunks(), false);
        self::assertCount(3, $chunks);
        self::assertStringContainsString('!!!', $chunks[1]);
        self::assertStringContainsString('RuntimeException: intentional job error', $chunks[1]);
        self::assertStringContainsString(': next phase ', $chunks[2]);
    }
    
    public function testAbandonedStreamCleansUpBatches(): void {
        $manager = new WorkManager(1);
        $manager->thenDo(SchedulerProbe::class, ['action' => 'abort']);
        $chunks = $manager->toChunks();
        self::assertStringContainsString(': abort ', $chunks->current());
        $chunks->next();
        self::assertSame(1, preg_match('/: ([a-f0-9]{32})\s+$/', $chunks->current(), $match));
        $batchId = $match[1] ?? throw new \RuntimeException('Expected the batch ID in the streamed log.');
        $pattern = sys_get_temp_dir() . '/cron-work-*/' . $batchId;
        $files = glob($pattern);
        self::assertIsArray($files);
        self::assertCount(1, $files);
        
        unset($chunks);
        
        self::assertSame([], glob($pattern));
    }
    
    public function testNativeWorkersCanHandOffABatch(): void {
        if (! WorkPool::isAvailable()) {
            self::markTestSkipped('Native worker handoff requires ext-parallel on PHP ZTS CLI.');
        }
        $directory = temp_dir('cron-handoff-test');
        $marker = $directory . DIRECTORY_SEPARATOR . 'card.json';
        try {
            $manager = new WorkManager(2);
            $manager->thenDo(SchedulerProbe::class, ['action' => 'producer', 'marker' => $marker])
                ->thenWait()
                ->thenDo(SchedulerProbe::class, ['action' => 'next phase']);
            $chunks = iterator_to_array($manager->toChunks(), false);
            
            self::assertCount(4, $chunks, implode('', $chunks));
            self::assertStringContainsString(': next phase ', $chunks[3]);
            self::assertSame('{"name":"Example","index":1}', file_get_contents($marker));
        } finally {
            if (is_file($marker)) {
                unlink($marker);
            }
            rmdir($directory);
        }
    }
}
