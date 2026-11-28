<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use RuntimeException;

final class SchedulerProbe extends AbstractWorkThread {
    private static ?string $workerId = null;
    
    protected function work(): void {
        $action = $this->getOption('action');
        $this->log($action);
        switch ($action) {
            case 'parent':
                $this->thenDo(self::class, ['action' => 'child']);
                break;
            case 'child':
                $this->thenDo(self::class, ['action' => 'grandchild']);
                break;
            case 'fail':
                throw new RuntimeException('intentional job error');
            case 'producer':
                $batch = $this->writeBatch([['name' => 'Example', 'index' => 1]]);
                $options = [
                    'batch' => $batch,
                    'marker' => $this->getOption('marker'),
                    'producer' => self::$workerId ??= bin2hex(random_bytes(8))
                ];
                $this->thenDo(self::class, ['action' => 'hold'] + $options);
                $this->thenDo(self::class, ['action' => 'consume'] + $options);
                break;
            case 'hold':
                $deadline = microtime(true) + 5;
                while (! is_file($this->getOption('marker')) && microtime(true) < $deadline) {
                    clearstatcache();
                    usleep(1000);
                }
                if (! is_file($this->getOption('marker'))) {
                    throw new RuntimeException('another worker did not complete the handoff');
                }
                break;
            case 'consume':
                if ((self::$workerId ??= bin2hex(random_bytes(8))) === $this->getOption('producer')) {
                    throw new RuntimeException('batch was consumed on its producer');
                }
                foreach ($this->readBatch($this->getOption('batch')) as $row) {
                    file_put_contents($this->getOption('marker'), json_encode($row, JSON_THROW_ON_ERROR));
                }
                $this->deleteBatch($this->getOption('batch'));
                break;
            case 'abort':
                $this->log($this->writeBatch([['name' => 'Abandoned']]));
                for ($i = 0; $i < 256; ++$i) {
                    $this->log('buffered log');
                }
                break;
        }
    }
}
