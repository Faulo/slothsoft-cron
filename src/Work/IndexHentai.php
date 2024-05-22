<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

class IndexHentai extends AbstractCronWork {

    protected function work(): void {
        $options = $this->getOptions();
        $fetchList = [];

        $targetRoot = $options['dest-root'];
        $name = $options['name'];

        $targetPath = $targetRoot . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;

        if (! is_dir($targetPath)) {
            mkdir($targetPath);
        }
        $options['dest-root'] = $targetPath;

        $options['source-uri'] = $options['source-host'] . $options['source-path'];
        $notFound = 0;

        do {
            $xpath = $options['downloader']->getXPath($options['source-uri']);
            if (! $xpath) {
                // $this->log(Storage::loadExternalFile($options['source-uri']));
                break;
            }
            $nodeList = $this->downloadNodeList($xpath, $options['source-xpath']);
            $nextURI = $this->downloadString($xpath, $options['source-xpath-next']);
            $nextURI = $this->_fixURI($nextURI, $options['source-uri']);
            if ($nodeList) {
                $notFound = 0;
                foreach ($nodeList as $node) {
                    $uri = $node->getAttribute('href');
                    if (strlen($uri)) {
                        $uri = $this->_fixURI($uri, $options['source-uri']);

                        $opt = $options;
                        $opt['source-uri'] = $uri;
                        $fetchList[$uri] = $opt;
                    }
                }
            } else {
                $notFound ++;
            }
            $options['source-uri'] = $nextURI;
        } while ($nextURI and $notFound < (int) $options['data-missing-count'] and count($fetchList) < $options['chapter-count']);

        $this->log(sprintf('Prepared to download %d manga of %s! (%s)', count($fetchList), $options['name'], $options['source-uri']));

        foreach ($fetchList as $fetch) {
            $this->thenDo(FetchHentai::class, $fetch);
        }
    }
}