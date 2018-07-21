<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;


class FetchManga extends AbstractCronWork
{

    protected function work() : void
    {
        $ret = [];
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
        for ($i = $options['page']; $i < $options['page-count']; $i ++) {
            $continue = false;
            $options['page'] = $i;
            $options['source-uri'] = $options['source-host'] . sprintf($options['source-path'], $options['chapter'], $options['page']);
            if (! $firstURI) {
                $firstURI = $options['source-uri'];
            }
            if ($lastExt) {
                $targetFile = sprintf($options['dest-file'], $options['page'], $ext);
                $target = $targetDir . $targetFile;
                if (file_exists($target)) {
                    $continue = true;
                    $lastData = file_get_contents($target);
                }
            }
            if (! $continue) {
                if ($img = $this->downloadURI($options['source-uri'], $options['source-xpath-image'])) {
                    if ($img === $lastImg) {} else {
                        $lastImg = $img;
                        
                        $ext = $img;
                        if (strlen($ext)) {
                            $ext = explode('.', $ext);
                            $ext = array_pop($ext);
                            if (strlen($ext)) {
                                $ext = explode('?', $ext);
                                $ext = array_shift($ext);
                                if (strlen($ext)) {
                                    $ext = explode('#', $ext);
                                    $ext = array_shift($ext);
                                }
                            }
                        }
                        $lastExt = $ext;
                        
                        $targetFile = sprintf($options['dest-file'], $options['page'], $ext);
                        
                        $target = $targetDir . $targetFile;
                        
                        if (file_exists($target)) {
                            // $ret[] = $target;
                            $continue = true;
                        } else {
                            @$data = file_get_contents($img);
                            
                            if ($data === $lastData) {} else {
                                $lastData = $data;
                                if (strlen($data) > $options['data-length-min']) {
                                    // $this->log(sprintf('downloading %s ...', $img));
                                    file_put_contents($target, $data);
                                    // $ret[] = $target;
                                    $continue = true;
                                    $pageCount ++;
                                } else {
                                    // $ret .= sprintf(' ERROR downloading %s! °A°%s', $img, PHP_EOL);
                                }
                            }
                        }
                    }
                } else {
                    // $this->log(sprintf('No manga page image? %s (%s)', $options['source-uri'], $options['source-xpath-image']), true);
                }
            }
            if ($continue) {
                $notFound = 0;
            } else {
                $notFound ++;
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
        return $ret;
    }

    
}