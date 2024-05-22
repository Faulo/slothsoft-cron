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
            $fileList = array_flip($fileList);
            $targetPath .= DIRECTORY_SEPARATOR;

            $options['dest-root'] = $targetPath;
            $options['type'] = 'file';

            if ($xpath = $this->downloadXPath($sourceURI)) {
                $nodeList = $xpath->evaluate($options['source-xpath']);
                foreach ($nodeList as $node) {
                    $name = $xpath->evaluate($options['source-xpath-name'], $node);
                    $uri = $this->_fixURI($xpath->evaluate($options['source-xpath-uri'], $node), $sourceURI);

                    if ($name and $uri) {
                        $file = $this->_fixFilename($name, $options['dest-ext']);
                        if (! isset($fileList[$file])) {
                            $options['dest-path'] = $targetPath . $file;
                            $options['source-uri'] = $uri;
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