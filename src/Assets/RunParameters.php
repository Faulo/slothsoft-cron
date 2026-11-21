<?php

namespace Slothsoft\Cron\Assets;

use Slothsoft\Farah\Module\Asset\ParameterFilterStrategy\AbstractMapParameterFilter;
use Slothsoft\Core\IO\Sanitizer\StringSanitizer;

class RunParameters extends AbstractMapParameterFilter {
    
    /** @return array<string, StringSanitizer> */
    protected function createValueSanitizers(): array {
        return [
            'ref' => new StringSanitizer('')
        ];
    }
}

