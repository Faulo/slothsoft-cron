<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;


class IndexPodcast extends AbstractCronWork
{


    protected function work() : void
    {
        $options = $this->getOptions();
        $fetchCount = 0;
        
        $targetRoot = $options['dest-root'];
        $name = $options['name'];
        $sourceHost = $options['source-host'];
        $sourcePath = $options['source-path'];
        
        $targetPath = $targetRoot . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
        
        if (! is_dir($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        if ($targetPath = realpath($targetPath)) {
            $targetPath .= DIRECTORY_SEPARATOR;
            
            $options['dest-root'] = $targetPath;
            $options['type'] = 'file';
            
            $uriList = $this->downloadURIList($sourceHost . $sourcePath, $options['source-xpath']);
            foreach ($uriList as $sourceURI) {
                if ($xpath = $this->downloadXPath($sourceURI)) {
                    $name = $xpath->evaluate('normalize-space(//h2)');
                    $uri = $this->_fixURI($xpath->evaluate('string(//a[normalize-space(.) = "Download"]/@href)'), $sourceURI);
                    
                    if ($name and $uri) {
                        $path = $targetPath . $this->_fixFilename($name, 'mp3');
                        if (! file_exists($path)) {
                            $options['dest-path'] = $path;
                            $options['source-uri'] = $uri;
                            $this->thenDo(FetchFile::class, $options);
                            $fetchCount++;
                        }
                    }
                }
            }
        }
        
        $this->log(sprintf('Prepared to download %d podcasts of %s!', $fetchCount, $options['name']));
    }

    
}