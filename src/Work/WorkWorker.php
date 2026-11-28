<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Composer\Autoload\ClassLoader;
use parallel\Channel;
use RuntimeException;
use Slothsoft\Core\ServerEnvironment;

/** @internal */
final class WorkWorker {
    /** @var array{root: string, cache: string, data: string, log: string, host: string, timezone: string} */
    private readonly array $environment;
    
    public static function findAutoloader(): string {
        foreach (ClassLoader::getRegisteredLoaders() as $vendorDirectory => $loader) {
            if ($loader->findFile(WorkManager::class) !== false) {
                return $vendorDirectory . DIRECTORY_SEPARATOR . 'autoload.php';
            }
        }
        throw new RuntimeException('Could not find the Composer autoloader for Cron workers.');
    }
    
    public function __construct() {
        $this->environment = [
            'root' => ServerEnvironment::getRootDirectory(),
            'cache' => ServerEnvironment::getCacheDirectory(),
            'data' => ServerEnvironment::getDataDirectory(),
            'log' => ServerEnvironment::getLogDirectory(),
            'host' => ServerEnvironment::getHostName(),
            'timezone' => date_default_timezone_get()
        ];
    }
    
    /** @return list<WorkInstruction> */
    public function run(WorkInstruction $instruction, Channel $logs, string $batchDirectory): array {
        $environment = $this->environment;
        ServerEnvironment::setRootDirectory($environment['root']);
        ServerEnvironment::setCacheDirectory($environment['cache']);
        ServerEnvironment::setDataDirectory($environment['data']);
        ServerEnvironment::setLogDirectory($environment['log']);
        ServerEnvironment::setHostName($environment['host']);
        date_default_timezone_set($environment['timezone']);
        
        $followUps = [];
        $instruction->run($logs->send(...), static function (WorkInstruction $next) use (&$followUps): void {
            $followUps[] = $next;
        }, new WorkBatchStore($batchDirectory));
        return $followUps;
    }
}
