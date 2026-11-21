<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use Slothsoft\Core\FileSystem;

class IndexFiles extends AbstractCronWork {
    
    protected function work(): void {
        $options = $this->getOptions();
        $fetchList = [];
        
        $targetRoot = $options['dest-root'];
        $sourceURI = $options['source-uri'];
        
        if ($targetPath = realpath($targetRoot)) {
            $fileList = FileSystem::scanDir($targetPath);
            $fileList = array_flip(array_filter($fileList, 'is_string'));
            $targetPath .= DIRECTORY_SEPARATOR;
            
            $options['dest-root'] = $targetPath;
            $options['type'] = 'file';
            
            if ($xpath = $this->downloadXPath($sourceURI)) {
                $nodeList = $this->downloadNodeList($xpath, $options['source-xpath']);
                foreach ($nodeList as $node) {
                    $name = $xpath->evaluate($options['source-xpath-name'], $node);
                    $uri = $xpath->evaluate($options['source-xpath-uri'], $node);
                    
                    if (is_string($name) && $name && is_string($uri) && $uri !== '') {
                        $file = $this->_fixFilename($name, $options['dest-ext']);
                        if (! isset($fileList[$file])) {
                            $options['dest-path'] = $targetPath . $file;
                            $options['source-uri'] = $this->_fixURI($uri, $sourceURI);
                            $fetchList[] = $options;
                            // break;
                        }
                    }
                }
                $this->log(sprintf('Prepared to download %d files of %s!', count($fetchList), $options['name']));
            }
        }
        foreach ($fetchList as $fetch) {
            $this->thenDo(FetchFile::class, $fetch);
        }
    }
}
