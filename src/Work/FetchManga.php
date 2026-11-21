<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

class FetchManga extends AbstractCronWork {
    
    protected function work(): void {
        $options = $this->getOptions();
        
        $lastImg = null;
        $lastData = null;
        $firstURI = null;
        $pageCount = 0;
        $chapterName = sprintf($options['dest-path'], $options['name'], $options['chapter']);
        $targetDir = $options['dest-root'] . $chapterName . DIRECTORY_SEPARATOR;
        if (! is_dir($targetDir)) {
            mkdir($targetDir);
        }
        $notFound = 0;
        $lastExt = null;
        for ($i = $options['page']; $i < $options['page-count']; $i++) {
            $continue = false;
            $options['page'] = $i;
            $options['source-uri'] = $options['source-host'] . sprintf($options['source-path'], $options['chapter'], $options['page']);
            if (! $firstURI) {
                $firstURI = $options['source-uri'];
            }
            if ($lastExt) {
                $targetFile = sprintf($options['dest-file'], $options['page'], $lastExt);
                $target = $targetDir . $targetFile;
                if (file_exists($target)) {
                    $continue = true;
                    $lastData = file_get_contents($target);
                }
            }
            if (! $continue && ($img = $this->downloadURI($options['source-uri'], $options['source-xpath-image'])) && $img !== $lastImg) {
                $lastImg = $img;
                
                $parts = explode('.', $img);
                $ext = array_pop($parts);
                $ext = explode('?', $ext, 2)[0];
                $ext = explode('#', $ext, 2)[0];
                $lastExt = $ext;
                
                $targetFile = sprintf($options['dest-file'], $options['page'], $ext);
                $target = $targetDir . $targetFile;
                if (file_exists($target)) {
                    $continue = true;
                } else {
                    @$data = file_get_contents($img);
                    if ($data !== $lastData) {
                        $lastData = $data;
                        if ($data !== false && strlen($data) > $options['data-length-min']) {
                            file_put_contents($target, $data);
                            $continue = true;
                            $pageCount++;
                        }
                    }
                }
            }
            if ($continue) {
                $notFound = 0;
            } else {
                $notFound++;
                if ($notFound > (int) $options['data-missing-count']) {
                    break;
                }
            }
        }
        if ($pageCount) {
            $this->log(sprintf('Downloaded %d pages for %s! (%s)', $pageCount, $chapterName, $firstURI), true);
        } else {
            $this->log(sprintf('Already here: %s! (%s)', $chapterName, $firstURI));
        }
    }
}
