<?php

use Slothsoft\Core\FS\DownloadManager;
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
    
    set_time_limit(Seconds::DAY);
    
    $doc = $this->getResourceDoc($config['input-resource'], 'xml');
    $xpath = self::loadXPath($doc);
    
    $manager = new DownloadManager($xpath);
    $manager->setConfig($config);
    $manager->setOptions($this->httpRequest->input);
    
    if ($config['output-stream']) {
        $ret = $manager->getStream();
    } else {
        $manager->run();
        $ret = $manager->getLog();
        $ret = HTTPFile::createFromString($ret);
    }
    
    return $ret;
});