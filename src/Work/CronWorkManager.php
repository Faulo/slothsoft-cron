<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use DomainException;

class CronWorkManager extends WorkManager {
    /** @return class-string<AbstractCronWork> */
    public function getIndexWork(string $type): string {
        switch ($type) {
            case 'comic':
                return IndexComic::class;
            case 'file':
                return IndexFile::class;
            case 'files':
                return IndexFiles::class;
            case 'hentai':
                return IndexHentai::class;
            case 'manga':
                return IndexManga::class;
            case 'php':
                return IndexPhp::class;
            case 'podcast':
                return IndexPodcast::class;
            case 'rss':
                return IndexRss::class;
            case 'tool':
                return IndexTool::class;
            default:
                throw new DomainException("Unknown cron update type: '$type'");
        }
    }
}

