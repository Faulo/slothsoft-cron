<?php
namespace Slothsoft\Cron\Work;

use Slothsoft\Farah\PThreads\WorkManager;
use DomainException;

class CronWorkManager extends WorkManager
{
    public function getIndexWork(string $type) : string {
        switch ($type) {
            case 'comic':   return IndexComic::class;
            case 'file':    return IndexFile::class;
            case 'files':   return IndexFiles::class;
            case 'hentai':  return IndexHentai::class;
            case 'manga':   return IndexManga::class;
            case 'php':     return IndexPhp::class;
            case 'podcast': return IndexPodcast::class;
            case 'rss':     return IndexRss::class;
            case 'tool':    return IndexTool::class;
            default:
                throw new DomainException("Unknown cron update type: '$type'");
        }
    }
}

