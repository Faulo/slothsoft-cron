<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Closure;
use Throwable;

/** @internal */
final class WorkInstruction {
    /**
     * @param class-string<AbstractWorkThread> $className
     * @param array<string, mixed> $options
     */
    public function __construct(public readonly string $className, public readonly array $options) {
    }
    
    /**
     * @param Closure(string): void $writeLog
     * @param Closure(self): void $schedule
     */
    public function run(Closure $writeLog, Closure $schedule, WorkBatchStore $batches): void {
        try {
            $className = $this->className;
            $work = new $className($this->options);
            $work->run($writeLog, $schedule, $batches);
        } catch (Throwable $error) {
            $writeLog($this->failure($error));
        }
    }
    
    public function failure(Throwable $error): string {
        return AbstractWorkThread::formatLog($this->className, $error::class . ': ' . $error->getMessage() . PHP_EOL . $error->getTraceAsString(), true);
    }
}
