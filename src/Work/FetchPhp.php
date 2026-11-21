<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Slothsoft\Core\IO\HTTPFile;
use Exception;

class FetchPhp extends AbstractCronWork {
    
    protected function work(): void {
        $options = $this->getOptions();
        
        $tempFile = HTTPFile::createFromPHP($options['source-uri']);
        $tempPath = $tempFile->getPath();
        $destPath = $options['dest-path'];
        
        if (file_exists($destPath) && md5_file($tempPath) === md5_file($destPath)) {
            $this->log(sprintf('File "%s" is already up to date!', $destPath));
            return;
        }
        if (! $tempFile->copyTo(dirname($destPath), basename($destPath), $options['copy-cmd'])) {
            $this->log(sprintf('Copy failed??? (%s to %s)', json_encode($tempPath), json_encode($destPath)), true);
            $this->log(base64_encode($destPath));
            return;
        }
        if (isset($options['dest-time'])) {
            touch($destPath, $options['dest-time']);
        }
        $this->log(sprintf('Updated file "%s"!', $destPath), true);
        if ($options['success-cmd']) {
            $successExec = sprintf($options['success-cmd'], escapeshellarg($destPath));
            $process = popen($successExec, 'r');
            if ($process !== false) {
                pclose($process);
            }
        }
        if ($options['success-php']) {
            try {
                $this->_eval($options['success-php']);
            } catch (Exception $e) {
                $this->log($e->getMessage(), true);
            }
        }
    }
}
