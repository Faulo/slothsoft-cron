<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Fiber;
use Generator;
use InvalidArgumentException;
use Slothsoft\Core\IO\Writable\ChunkWriterInterface;
use SplQueue;
use Throwable;

class WorkManager implements ChunkWriterInterface {
    private readonly int $workerCount;
    
    /** @var list<list<WorkInstruction>> */
    private array $phases = [];
    
    /** @var list<WorkInstruction> */
    private array $pending = [];
    
    public function __construct(int $treadCount) {
        if ($treadCount < 1) {
            throw new InvalidArgumentException('The worker count must be positive.');
        }
        $this->workerCount = $treadCount;
    }
    
    /**
     * @param class-string<AbstractWorkThread> $className
     * @param array<string, mixed> $options
     */
    public function thenDo(string $className, array $options = []): self {
        $this->pending[] = new WorkInstruction($className, $options);
        return $this;
    }
    
    public function thenWait(): self {
        $this->phases[] = $this->pending;
        $this->pending = [];
        return $this;
    }
    
    /** @return Generator<int, string> */
    public function toChunks(): Generator {
        $pool = null;
        $batches = null;
        try {
            foreach ([...$this->phases, $this->pending] as $phase) {
                if ($phase === []) {
                    continue;
                }
                $queue = new SplQueue();
                foreach ($phase as $instruction) {
                    $queue->enqueue($instruction);
                }
                
                $batches ??= WorkBatchStore::create();
                $pool ??= WorkPool::isAvailable() ? new WorkPool($this->workerCount, $batches->directory) : null;
                $chunks = $pool === null ? self::runInline($queue, $batches) : $pool->runPhase($queue);
                foreach ($chunks as $chunk) {
                    yield $chunk;
                }
            }
        } catch (Throwable $error) {
            yield (string) $error;
        } finally {
            // Close the active phase before releasing its workers and batches.
            unset($chunks);
            try {
                $pool?->close();
            } finally {
                $batches?->close();
            }
        }
    }
    
    /**
     * @param SplQueue<WorkInstruction> $queue
     * @return Generator<int, string>
     */
    private static function runInline(SplQueue $queue, WorkBatchStore $batches): Generator {
        while (! $queue->isEmpty()) {
            $instruction = $queue->dequeue();
            $fiber = new Fiber(static function () use ($instruction, $queue, $batches): void {
                $instruction->run(static function (string $log): void {
                    Fiber::suspend($log);
                }, $queue->enqueue(...), $batches);
            });
            
            $chunk = $fiber->start();
            while ($fiber->isSuspended()) {
                yield $chunk;
                $chunk = $fiber->resume();
            }
        }
    }
}
