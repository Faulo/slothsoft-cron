<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use Slothsoft\Core\IO\HTTPFile;
use Exception;

class FetchPhp extends AbstractCronWork
{

    protected function work() : void
    {
        if ($tempFile = HTTPFile::createFromPHP($this->getOption('source-uri'))) {
            $tempPath = $tempFile->getPath();
            $destPath = $this->getOption('dest-path');
            
            $copy = true;
            if (file_exists($destPath)) {
                if (md5_file($tempPath) === md5_file($destPath)) {
                    $this->log(sprintf('File "%s" is already up to date!', $destPath));
                    $copy = false;
                }
            }
            if ($copy) {
                if ($tempFile->copyTo(dirname($destPath), basename($destPath), $this->getOption('copy-cmd'))) {
                    // $copyExec = sprintf($this->getOption('copy-cmd'), escapeshellarg($tempPath), escapeshellarg($destPath));
                    // $res = exec($copyExec);
                    // if (file_exists($destPath)) {
                    if ($this->getOption('dest-time')) {
                        touch($destPath, $this->getOption('dest-time'));
                    }
                    $this->log(sprintf('Updated file "%s"!', $destPath), true);
                    if ($this->getOption('success-cmd')) {
                        $successExec = sprintf($this->getOption('success-cmd'), escapeshellarg($destPath));
                        // $res = exec($successExec);
                        pclose(popen($successExec, 'r')); // async maybe
                    }
                    // $this->log($this->getOption('success-php'));
                    if ($this->getOption('success-php')) {
                        try {
                            $res = $this->_eval($this->getOption('success-php'));
                        } catch (Exception $e) {
                            $this->log($e->getMessage(), true);
                        }
                    }
                } else {
                    $this->log(sprintf('Copy failed??? (%s to %s)', json_encode($tempPath), json_encode($destPath)), true);
                    $this->log(base64_encode($destPath));
                    // $this->log(sprintf('Copy failed??? (%s)', $copyExec), true);
                    // $this->log(json_encode($res));
                }
                // my_dump($res);
            }
        } else {
            $this->log(sprintf('PHP failed??? (%s)', $downloadExec), true);
            $this->log(json_encode($res));
        }
    }

}