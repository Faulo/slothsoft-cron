<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

class IndexPhp extends AbstractCronWork {

    protected function work(): void {
        $options = $this->getOptions();

        $this->log(sprintf('Prepared to execute %s!', $options['name']));
        $this->thenDo(FetchPhp::class, $options);
    }
}