<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;


class IndexPhp extends AbstractCronWork
{

    protected function work() : void
    {
        $this->log(sprintf('Prepared to execute %s!', $this->getName()));
        $this->thenDo(FetchPhp::class, $this->getOptions());
    }
}