<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;


class IndexFile extends AbstractCronWork
{

    protected function work() : void
    {
        $ret = [];
        $ret[] = $options;
        $this->log(sprintf('Prepared to download %s!', $options['name']));
        return $ret;
    }
}