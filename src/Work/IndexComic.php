<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use Slothsoft\Core\Storage;
use Slothsoft\Core\Calendar\Seconds;

class IndexComic extends AbstractCronWork
{

    protected function work() : void
    {
        $options = $this->getOptions();
        $fetchList = [];
        
        $targetRoot = $options['dest-root'];
        $targetURI = $options['dest-uri'];
        $sourceURI = $options['source-uri'];
        $blackList = $options['blacklist'];
        $blackList = explode("\n", trim($blackList));
        foreach ($blackList as &$val) {
            $val = trim($val);
        }
        unset($val);
        
        if (! is_dir($targetRoot)) {
            mkdir($targetRoot, 0777, true);
        }
        
        if ($targetPath = realpath($targetRoot)) {
            $i = 1;
            $comicList = [];
            
            do {
                $nextURI = null;
                if (isset($comicList[$sourceURI])) {
                    break;
                }
                $xpath = Storage::loadExternalXPath($sourceURI, Seconds::YEAR);
                if ($xpath) {
                    if ($xpath) {
                        $title = $xpath->evaluate($options['source-xpath-title']);
                        $nextURI = $xpath->evaluate($options['source-xpath-uri']);
                        $image = $xpath->evaluate($options['source-xpath-image']);
                        
                        $nextURI = strlen($nextURI) ? $this->_fixURI($nextURI, $sourceURI) : null;
                        $image = strlen($image) ? $this->_fixURI($image, $sourceURI) : null;
                        
                        if ($image) {
                            if (! strlen($title)) {
                                $title = "#$i";
                            }
                            $ext = substr($image, strrpos($image, '.'));
                            $name = sprintf('%04d%s', $i, $ext);
                            $path = sprintf('%s%s%s', $targetPath, DIRECTORY_SEPARATOR, $name);
                            // $thumbFile = sprintf('%s%s%04d.png', $thumbDir, DIRECTORY_SEPARATOR, $i);
                            
                            $arr = [];
                            $arr['key'] = sprintf('%04d', $i);
                            $arr['title'] = $title;
                            $arr['href'] = $sourceURI;
                            $arr['source'] = $image;
                            $arr['path'] = $path;
                            $arr['image'] = $targetURI . $name;
                            if (in_array($title, $blackList)) {
                                $arr['hidden'] = '';
                            }
                            
                            $comicList[$sourceURI] = $arr;
                            $i ++;
                        }
                    }
                }
                $sourceURI = $nextURI;
            } while ($sourceURI and $i < $options['page-count']);
            
            $this->log(sprintf('Prepared to verify %d comic strips of %s!', count($comicList), $options['name']));
            
            $options['comicList'] = array_values($comicList);
            $fetchList[] = $options;
        }
        
        foreach ($fetchList as $fetch) {
            $this->thenDo(FetchComic::class, $fetch);
        }
    }

    
}