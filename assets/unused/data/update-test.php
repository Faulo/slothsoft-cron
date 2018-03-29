<?php

use Slothsoft\Core\IO\HTTPFile;
use Slothsoft\Farah\HTTPClosure;

return new HTTPClosure([
    'isThreaded' => true
], function () {
    $config = [];
    $config['input-resource'] = 'cron/update-files';
    $config['output-stream'] = true;
    $config['threads-active'] = true;
    $config['threads-count'] = 16;
    
    return HTTPFile::createFromString(print_r($config, true));
});