<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use Slothsoft\Core\Image;
use Slothsoft\Core\IO\HTTPFile;
use DOMDocument;

class FetchComic extends AbstractCronWork
{
    protected function work(): void
    {
        $options = $this->getOptions();
        
        $downloadCount = 0;
        foreach ($options['comicList'] as &$comic) {
            if ($downloadCount < $options['download-count'] and ! file_exists($comic['path'])) {
                if ($file = HTTPFile::createFromURL($comic['source'])) {
                    $downloadCount ++;
                    $file->copyTo(dirname($comic['path']), basename($comic['path']));
                }
            }
            if (file_exists($comic['path'])) {
                $comic += Image::imageInfo($comic['path']);
                // $this->log($comic);
            }
        }
        unset($comic);
        
        $destFile = $options['dest-root'] . DIRECTORY_SEPARATOR . 'index.xml';
        $doc = new DOMDocument();
        $parentNode = $doc->createElement('comic');
        $parentNode->setAttribute('name', $options['name']);
        foreach ($options['comicList'] as $comic) {
            $node = $doc->createElement('page');
            foreach ($comic as $key => $val) {
                $node->setAttribute($key, $val);
            }
            $parentNode->appendChild($node);
        }
        $doc->appendChild($parentNode);
        $doc->save($destFile);
        $this->log(sprintf('Created index file %s containing %d pages for %s!', $destFile, count($options['comicList']), $options['name']));
    }
}