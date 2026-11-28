<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Closure;
use Generator;
use LogicException;

abstract class AbstractWorkThread {
    /** @var Closure(string): void|null */
    private ?Closure $writeLog = null;
    
    /** @var Closure(WorkInstruction): void|null */
    private ?Closure $schedule = null;
    
    private ?WorkBatchStore $batches = null;
    
    /** @param array<string, mixed> $options */
    public function __construct(private array $options) {
    }
    
    abstract protected function work(): void;
    
    /**
     * @param Closure(string): void $writeLog
     * @param Closure(WorkInstruction): void $schedule
     * @internal
     */
    final public function run(Closure $writeLog, Closure $schedule, WorkBatchStore $batches): void {
        $this->writeLog = $writeLog;
        $this->schedule = $schedule;
        $this->batches = $batches;
        try {
            $this->work();
        } finally {
            $this->writeLog = null;
            $this->schedule = null;
            $this->batches = null;
        }
    }
    
    /** @return array<string, mixed> */
    protected function getOptions(): array {
        return $this->options;
    }
    
    protected function getOption(string $key): mixed {
        return $this->options[$key] ?? null;
    }
    
    protected function log(mixed $message, bool $isImportant = false): void {
        $writeLog = $this->writeLog ?? throw new LogicException('Work is not running.');
        $writeLog(self::formatLog(static::class, $message, $isImportant));
    }
    
    /**
     * @param class-string<AbstractWorkThread> $className
     * @param array<string, mixed> $options
     */
    protected function thenDo(string $className, array $options = []): void {
        $schedule = $this->schedule ?? throw new LogicException('Work is not running.');
        $schedule(new WorkInstruction($className, $options));
    }
    
    /** @param iterable<array<string, mixed>> $rows */
    final protected function writeBatch(iterable $rows): string {
        return ($this->batches ?? throw new LogicException('Work is not running.'))->write($rows);
    }
    
    /** @return Generator<int, array<string, mixed>> */
    final protected function readBatch(string $id): Generator {
        return ($this->batches ?? throw new LogicException('Work is not running.'))->read($id);
    }
    
    final protected function deleteBatch(string $id): void {
        ($this->batches ?? throw new LogicException('Work is not running.'))->delete($id);
    }
    
    /** @internal */
    final public static function formatLog(string $className, mixed $message, bool $isImportant): string {
        if (! is_string($message)) {
            $message = print_r($message, true);
        }
        return sprintf('[%s] %s %s: %s %s', date('d.m.y H:i:s'), $isImportant ? '!!!' : '   ', basename($className), $message, PHP_EOL);
    }
}
