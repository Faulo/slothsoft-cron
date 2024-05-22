<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

class IndexRss extends AbstractCronWork {

    protected function work(): void {
        $options = $this->getOptions();
        $fetchCount = 0;

        $targetRoot = $options['dest-root'];
        $name = $options['name'];
        $sourceHost = $options['source-host'];
        $sourcePath = $options['source-path'];

        $targetPath = $targetRoot . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;

        if (! is_dir($targetPath)) {
            mkdir($targetPath);
        }
        $options['dest-root'] = $targetPath;
        $options['type'] = 'file';

        $sourceURI = $sourceHost . $sourcePath;
        if ($xpath = $this->downloadXPath($sourceURI)) {
            $itemNodeList = $xpath->evaluate(sprintf('//item[enclosure][contains(title, "%s")]', $name));
            foreach ($itemNodeList as $itemNode) {
                $title = $xpath->evaluate('normalize-space(title)', $itemNode);
                $time = $xpath->evaluate('normalize-space(pubDate)', $itemNode);
                $time = strtotime($time);
                $uri = $this->_fixURI($xpath->evaluate('normalize-space(enclosure/@url)', $itemNode), $sourceURI);
                // $type = $xpath->evaluate('normalize-space(enclosure/@type)', $itemNode);
                $file = pathinfo($uri, PATHINFO_BASENAME);
                $file = preg_replace('/\?.*/', '', $file);
                $ext = pathinfo($file, PATHINFO_EXTENSION);

                if ($title and $uri) {
                    $name = $title;
                    $match = null;
                    if (isset($options['preg-file']) and preg_match($options['preg-file'], $file, $match)) {
                        $name = sprintf('%03d', $match[1]);
                    }
                    if (isset($options['preg-title']) and preg_match($options['preg-title'], $title, $match)) {
                        $name = sprintf('%03d - %s', $match[1], $match[2]);
                    }
                    $file = $this->_fixFilename($name, $ext);

                    $path = $targetPath . $file;
                    if (file_exists($path)) {
                        if ($time > 0) {
                            touch($path, $time);
                        }
                    } else {
                        $options['dest-path'] = $path;
                        $options['source-uri'] = $uri;
                        if ($time > 0) {
                            $options['dest-time'] = $time;
                        }
                        $this->thenDo(FetchFile::class, $options);
                        $fetchCount ++;
                    }
                }
            }
        }

        $this->log(sprintf('Prepared to download %d podcasts of %s!', $fetchCount, $options['name']));
    }
}