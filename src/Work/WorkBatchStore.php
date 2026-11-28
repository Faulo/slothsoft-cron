<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Generator;
use RuntimeException;
use SplFileInfo;

/** @internal */
final class WorkBatchStore {
    public function __construct(public readonly string $directory) {
    }
    
    public static function create(): self {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cron-work-' . bin2hex(random_bytes(16));
        if (! mkdir($directory, 0700)) {
            throw new RuntimeException('Could not create the work batch directory.');
        }
        return new self($directory);
    }
    
    /** @param iterable<array<string, mixed>> $rows */
    public function write(iterable $rows): string {
        $id = bin2hex(random_bytes(16));
        $stream = fopen($this->path($id), 'xb');
        if ($stream === false) {
            throw new RuntimeException('Could not create the work batch.');
        }
        try {
            foreach ($rows as $row) {
                $line = json_encode($row, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION) . "\n";
                if (fwrite($stream, $line) !== strlen($line)) {
                    throw new RuntimeException('Could not write the work batch.');
                }
            }
        } finally {
            fclose($stream);
        }
        return $id;
    }
    
    /** @return Generator<int, array<string, mixed>> */
    public function read(string $id): Generator {
        $stream = fopen($this->path($id), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Could not open the work batch.');
        }
        try {
            while (($line = fgets($stream)) !== false) {
                /** @var array<string, mixed> $row */
                $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                yield $row;
            }
            if (! feof($stream)) {
                throw new RuntimeException('Could not read the work batch.');
            }
        } finally {
            fclose($stream);
        }
    }
    
    public function delete(string $id): void {
        unlink($this->path($id));
    }
    
    public function close(): void {
        foreach (new \FilesystemIterator($this->directory) as $file) {
            if ($file instanceof SplFileInfo) {
                unlink($file->getPathname());
            }
        }
        rmdir($this->directory);
    }
    
    private function path(string $id): string {
        if (! preg_match('/\A[0-9a-f]{32}\z/', $id)) {
            throw new \InvalidArgumentException('Invalid work batch identifier.');
        }
        return $this->directory . DIRECTORY_SEPARATOR . $id;
    }
}
