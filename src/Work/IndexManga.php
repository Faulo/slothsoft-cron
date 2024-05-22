<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use Slothsoft\Core\FileSystem;

class IndexManga extends AbstractCronWork {

    protected function work(): void {
        $options = $this->getOptions();
        $fetchCount = 0;

        $targetRoot = $options['dest-root'];
        $name = $options['name'];

        $targetPath = $targetRoot . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;

        if (! is_dir($targetPath)) {
            mkdir($targetPath);
        }
        $options['dest-root'] = $targetPath;

        $startChapter = 0;
        $chapterList = FileSystem::scanDir($targetPath, FileSystem::SCANDIR_EXCLUDE_FILES);
        foreach ($chapterList as $chapter) {
            $match = null;
            if (preg_match('/(\d+)/', $chapter, $match)) {
                $no = (int) $match[1];
                if ($no > $startChapter) {
                    $startChapter = $no;
                }
            }
        }
        if (isset($options['chapter-start'])) {
            $startChapter = (int) $options['chapter-start'];
        }
        $notFound = 0;
        for ($i = $startChapter; $i - $startChapter < $options['chapter-count']; $i ++) {
            $options['chapter'] = $i;
            $options['page'] = 1;
            $options['source-uri'] = $options['source-host'] . sprintf($options['source-path'], $options['chapter'], $options['page']);
            // $this->log($options['source-uri']);
            // $this->log($options);
            if ($this->downloadString($options['source-uri'], $options['source-xpath-image'])) {
                $notFound = 0;
                $this->thenDo(FetchManga::class, $options);
                $fetchCount ++;
            } else {
                $notFound ++;
                if ($notFound > (int) $options['data-missing-count']) {
                    break;
                }
            }
        }
        $this->log(sprintf('Prepared to download %d chapter(s) of %s! (%s)', $fetchCount, $options['name'], $options['source-uri']));
    }
}