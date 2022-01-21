<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;


class IndexFile extends AbstractCronWork
{

    protected function work() : void
    {
        $options = $this->getOptions();
        
        $this->log(sprintf('Prepared to download %s!', $options['name']));
        $this->thenDo(FetchFile::class, $options);
    }
}