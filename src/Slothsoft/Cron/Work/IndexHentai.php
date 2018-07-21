<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;


class IndexHentai extends AbstractCronWork
{

    protected function work() : void
    {
        $ret = [];
        $targetRoot = $options['dest-root'];
        $name = $options['name'];
        $sourceHost = $options['source-host'];
        $sourcePath = $options['source-path'];
        
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
                        $ret[$uri] = $opt;
                    }
                    /*
                     * $id = preg_match('/\d+/', $uri, $match)
                     * ? (int) $match[0]
                     * : null;
                     * $title = $node->textContent;
                     * $title = FileSystem::filenameSanitize($title);
                     * if ($uri and $title) {
                     * $options['chapter'] = $title;
                     * $options['dest-path'] = $title . DIRECTORY_SEPARATOR;
                     * $options['source-uri'] = $uri;
                     * if (isset($options['source-xpath-download'])) {
                     * //hentai.ms
                     * if (!is_dir($options['dest-root'] . $options['dest-path'])) {
                     * $ret[$uri] = $options;
                     * }
                     * } else {
                     * //nhentai.net
                     * if ($id) {
                     * $options['chapter'] = $id;
                     * $options['page'] = 1;
                     * $options['type'] = 'manga';
                     * $options['source-path'] = '/g/%d/%d/';
                     *
                     * $ret[$uri] = $options;
                     * }
                     * }
                     * }
                     * //
                     */
                }
            } else {
                $notFound ++;
            }
            $options['source-uri'] = $nextURI;
        } while ($nextURI and $notFound < (int) $options['data-missing-count'] and count($ret) < $options['chapter-count']);
        $this->log(sprintf('Prepared to download %d manga of %s! (%s)', count($ret), $options['name'], $options['source-uri']));
        return $ret;
    }

    
}