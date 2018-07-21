<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use Slothsoft\Core\IO\HTTPFile;
use Exception;

class FetchFile extends AbstractCronWork
{

    protected function work() : void
    {
        $ret = [];
        if ($tempFile = HTTPFile::createFromURL($options['source-uri'])) {
            $tempPath = $tempFile->getPath();
            $destPath = $options['dest-path'];
            
            $copy = true;
            if (file_exists($destPath)) {
                if (md5_file($tempPath) === md5_file($destPath)) {
                    $this->log(sprintf('File "%s" is already up to date!', $destPath));
                    $copy = false;
                }
            }
            if ($copy) {
                if ($tempFile->copyTo(dirname($destPath), basename($destPath), $options['copy-cmd'])) {
                    // $copyExec = sprintf($options['copy-cmd'], escapeshellarg($tempPath), escapeshellarg($destPath));
                    // $res = exec($copyExec);
                    // if (file_exists($destPath)) {
                    if (isset($options['dest-time'])) {
                        touch($destPath, $options['dest-time']);
                    }
                    $this->log(sprintf('Updated file "%s"!', $destPath), true);
                    if ($options['success-cmd']) {
                        $successExec = sprintf($options['success-cmd'], escapeshellarg($destPath));
                        // $res = exec($successExec);
                        pclose(popen($successExec, 'r')); // async maybe
                    }
                    // $this->log($options['success-php']);
                    if ($options['success-php']) {
                        try {
                            $res = $this->_eval($options['success-php']);
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
            $this->log(sprintf('Download failed??? (%s)', $options['source-uri']), true);
        }
        return $ret;
    }

    
}