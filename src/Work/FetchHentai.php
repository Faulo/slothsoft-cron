<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use Slothsoft\Core\FileSystem;
use Slothsoft\Core\IO\HTTPFile;

class FetchHentai extends AbstractCronWork
{

    protected function work() : void
    {
        $options = $this->getOptions();
        
        if (isset($options['source-xpath-download'])) {
            if ($uri = $this->downloadURI($options['source-uri'], $options['source-xpath-download'])) {
                if ($file = HTTPFile::createFromURL($uri)) {
                    FileSystem::extractArchive($file->getPath(), $options['dest-root'] . $options['dest-path']);
                    $this->log(sprintf('Downloaded "%s"!', $options['chapter']));
                } else {
                    $this->log(sprintf('Download Archive not found: %s!', $uri));
                }
            } else {
                $this->log(sprintf('Download URL not found: %s! (%s)', $options['source-uri'], $options['source-xpath-download']));
            }
        }
        if (isset($options['source-xpath-read'])) {
            $xpath = $options['downloader']->getXPath($options['source-uri']);
            if ($xpath) {
                $title = $this->downloadString($xpath, $options['source-xpath-title']);
                $title = FileSystem::filenameSanitize($title);
                $uri = $this->downloadString($xpath, $options['source-xpath-read']);
                $uri = $this->_fixURI($uri, $options['source-uri']);
                
                $path = $options['dest-root'] . $title . DIRECTORY_SEPARATOR;
                
                if (strlen($title) and strlen($uri)) {
                    $firstPage = true;
                    
                    $xpath = $options['downloader']->getXPath($uri);
                    foreach ($xpath->evaluate('//script') as $scriptNode) {
                        $match = null;
                        if (preg_match('~var chapters = ([^;]+);~', $scriptNode->textContent, $match)) {
                            $chapters = $match[1];
                            $chapters = json_decode($chapters, true);
                            if ($chapters) {
                                foreach ($chapters as $chapter) {
                                    $image = $chapter['image'];
                                    $file = sprintf('%s%03d.%s', $path, $chapter['page'], pathinfo($chapter['image'], PATHINFO_EXTENSION));
                                    
                                    if (file_exists($file)) {
                                        // nothing to do \o/
                                    } else {
                                        if ($data = $options['downloader']->getFile($image)) {
                                            if ($firstPage) {
                                                if (! is_dir($path)) {
                                                    mkdir($path, 0777, true);
                                                }
                                                $this->log(sprintf('Downloading hentai "%s" (%s)', $title, $uri));
                                                $firstPage = false;
                                            }
                                            file_put_contents($file, $data);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    
}