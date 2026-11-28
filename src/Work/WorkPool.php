<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Generator;
use LogicException;
use parallel\Channel;
use parallel\Events;
use parallel\Events\Event;
use parallel\Events\Event\Type;
use parallel\Runtime;
use RuntimeException;
use SplQueue;
use Throwable;

/** @internal */
final class WorkPool {
    /** @var array<int, Runtime> */
    private array $runtimes = [];
    
    /** @var list<int> */
    private array $idle = [];
    
    private readonly string $autoloader;
    
    private readonly WorkWorker $execution;
    
    public static function isAvailable(): bool {
        return PHP_SAPI === 'cli' && extension_loaded('parallel');
    }
    
    public function __construct(private readonly int $workerCount, private readonly string $batchDirectory) {
        $this->autoloader = WorkWorker::findAutoloader();
        $this->execution = new WorkWorker();
    }
    
    /**
     * @param SplQueue<WorkInstruction> $queue
     * @return Generator<int, string>
     */
    public function runPhase(SplQueue $queue): Generator {
        $logs = new Channel(128);
        $events = new Events();
        $events->addChannel($logs);
        $running = [];
        $poll = static function () use ($events, $logs): Event {
            $event = $events->poll() ?? throw new LogicException('The active work pool has no events.');
            if ($event->object instanceof Channel) {
                $events->addChannel($logs);
            }
            return $event;
        };
        
        try {
            while (! $queue->isEmpty() || $running !== []) {
                while (! $queue->isEmpty() && count($running) < $this->workerCount) {
                    $instruction = $queue->dequeue();
                    $worker = array_pop($this->idle) ?? count($this->runtimes);
                    $source = 'worker-' . $worker;
                    try {
                        $runtime = $this->runtimes[$worker] ??= new Runtime($this->autoloader);
                        $future = $runtime->run(
                            static function (WorkInstruction $instruction, Channel $logs, string $directory, WorkWorker $execution): array {
                                return $execution->run($instruction, $logs, $directory);
                            },
                            [$instruction, $logs, $this->batchDirectory, $this->execution]
                        ) ?? throw new LogicException('Worker did not return a future.');
                        $events->addFuture($source, $future);
                        $running[$source] = [$worker, $instruction];
                    } catch (Throwable $error) {
                        $this->idle[] = $worker;
                        yield $instruction->failure($error);
                    }
                }
                
                if ($running === []) {
                    continue;
                }
                $event = $poll();
                if ($event->object instanceof Channel) {
                    yield $event->value;
                    continue;
                }
                
                [$worker, $instruction] = $running[$event->source];
                unset($running[$event->source]);
                $this->idle[] = $worker;
                if ($event->type === Type::Read && is_array($event->value)) {
                    foreach ($event->value as $followUp) {
                        $queue->enqueue($followUp);
                    }
                } else {
                    $error = $event->value instanceof Throwable ? $event->value : new RuntimeException('Worker exited before returning its follow-up jobs.');
                    yield $instruction->failure($error);
                }
            }
            
            // A completed future may be delivered ahead of the job's last buffered logs.
            $events->setBlocking(false);
            while ($event = $events->poll()) {
                $events->addChannel($logs);
                yield $event->value;
            }
        } finally {
            // Drain active jobs so a sender waiting on a full log buffer can finish.
            while ($running !== []) {
                $event = $poll();
                if (! $event->object instanceof Channel) {
                    unset($running[$event->source]);
                }
            }
            $logs->close();
        }
    }
    
    public function close(): void {
        foreach ($this->runtimes as $runtime) {
            $runtime->close();
        }
    }
}
